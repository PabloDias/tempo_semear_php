#!/bin/bash

# =============================================================================
# SCRIPT DE DEPLOY - SISTEMA TEMPO DE SEMEAR
# =============================================================================
# Uso: ./scripts/deploy.sh [desenvolvimento|producao]
# =============================================================================

set -e  # Para na primeira falha

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funções de log
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# Banner
echo "=============================================="
echo "  DEPLOY - SISTEMA TEMPO DE SEMEAR"
echo "=============================================="
echo ""

# Verifica parâmetro
AMBIENTE=${1:-desenvolvimento}

if [[ "$AMBIENTE" != "desenvolvimento" && "$AMBIENTE" != "producao" ]]; then
    log_error "Ambiente inválido. Use: desenvolvimento ou producao"
    exit 1
fi

log_info "Ambiente selecionado: $AMBIENTE"
echo ""

# Verifica se Docker está instalado
if ! command -v docker &> /dev/null; then
    log_error "Docker não está instalado!"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    log_error "Docker Compose não está instalado!"
    exit 1
fi

log_success "Docker e Docker Compose detectados"

# Verifica arquivo .env
if [ ! -f .env ]; then
    log_warning "Arquivo .env não encontrado!"
    
    if [ "$AMBIENTE" == "producao" ]; then
        log_error "Arquivo .env é obrigatório em produção!"
        exit 1
    else
        log_info "Criando .env de exemplo..."
        cat > .env << 'EOF'
DB_PASSWORD=dev_password_123
DB_HOST=db
DB_PORT=5432
DB_NAME=semeadordb
DB_USER=semeadoruser
EOF
        log_success "Arquivo .env criado"
    fi
fi

# Cria diretórios necessários
log_info "Criando diretórios..."
mkdir -p uploads backups logs/nginx ssl
chmod 755 uploads backups
log_success "Diretórios criados"

# Cria arquivos .gitkeep
touch uploads/.gitkeep backups/.gitkeep logs/.gitkeep ssl/.gitkeep

# Para containers existentes
log_info "Parando containers antigos..."
docker-compose down 2>/dev/null || true
log_success "Containers parados"

# Escolhe o arquivo docker-compose correto
if [ "$AMBIENTE" == "producao" ]; then
    COMPOSE_FILE="docker-compose.prod.yml"
    
    # Verifica se arquivo de produção existe
    if [ ! -f "$COMPOSE_FILE" ]; then
        log_error "Arquivo $COMPOSE_FILE não encontrado!"
        exit 1
    fi
    
    # Verifica SSL em produção
    if [ ! -f ssl/cert.pem ] || [ ! -f ssl/key.pem ]; then
        log_warning "Certificados SSL não encontrados!"
        log_info "Configure SSL antes de continuar em produção"
        read -p "Deseja continuar mesmo assim? (s/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Ss]$ ]]; then
            exit 1
        fi
    fi
else
    COMPOSE_FILE="docker-compose.yml"
fi

# Build e start dos containers
log_info "Construindo e iniciando containers..."
docker-compose -f "$COMPOSE_FILE" up -d --build

# Aguarda o PostgreSQL ficar pronto
log_info "Aguardando PostgreSQL inicializar..."
sleep 10

# Verifica se o PostgreSQL está pronto
MAX_TRIES=30
TRIES=0
until docker exec tds-postgres pg_isready -U semeadoruser -d semeadordb &>/dev/null || [ $TRIES -eq $MAX_TRIES ]; do
    sleep 1
    ((TRIES++))
    echo -n "."
done

if [ $TRIES -eq $MAX_TRIES ]; then
    log_error "PostgreSQL não inicializou no tempo esperado"
    exit 1
fi

log_success "PostgreSQL pronto"

# Verifica se as tabelas já existem
log_info "Verificando banco de dados..."
TABLES_EXIST=$(docker exec tds-postgres psql -U semeadoruser -d semeadordb -tAc "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public' AND table_name='cadastros'")

if [ "$TABLES_EXIST" == "0" ]; then
    log_info "Banco vazio. Executando migrações..."
    
    # Copia arquivos SQL para o container
    if [ -f "01_create-tables-sql_cp.sql" ]; then
        docker cp 01_create-tables-sql_cp.sql tds-postgres:/tmp/
        docker exec tds-postgres psql -U semeadoruser -d semeadordb -f /tmp/01_create-tables-sql_cp.sql
        log_success "Tabelas criadas"
    else
        log_warning "Arquivo 01_create-tables-sql_cp.sql não encontrado"
    fi
    
    if [ -f "02_create-triggers-sql.sql" ]; then
        docker cp 02_create-triggers-sql.sql tds-postgres:/tmp/
        docker exec tds-postgres psql -U semeadoruser -d semeadordb -f /tmp/02_create-triggers-sql.sql
        log_success "Triggers criados"
    else
        log_warning "Arquivo 02_create-triggers-sql.sql não encontrado"
    fi
    
    if [ -f "03_insert_states_cities.sql" ]; then
        docker cp 03_insert_states_cities.sql tds-postgres:/tmp/
        docker exec tds-postgres psql -U semeadoruser -d semeadordb -f /tmp/03_insert_states_cities.sql
        log_success "Estados e municípios populados"
    else
        log_warning "Arquivo 03_insert_states_cities.sql não encontrado"
    fi
    
    # Pergunta se deseja criar admin
    log_info "Deseja criar um usuário administrador?"
    read -p "Criar admin agora? (S/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Nn]$ ]]; then
        docker exec -it tds-php php /var/www/scripts/criar_admin.php
    fi
else
    log_success "Banco de dados já inicializado"
fi

# Verifica status dos containers
log_info "Verificando status dos containers..."
docker-compose -f "$COMPOSE_FILE" ps

# Testa conexão
log_info "Testando conexão..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080 || echo "000")

if [ "$HTTP_CODE" == "200" ] || [ "$HTTP_CODE" == "302" ]; then
    log_success "Sistema está respondendo (HTTP $HTTP_CODE)"
else
    log_warning "Sistema retornou código HTTP: $HTTP_CODE"
fi

# Instruções finais
echo ""
echo "=============================================="
log_success "DEPLOY CONCLUÍDO!"
echo "=============================================="
echo ""
log_info "URLs de Acesso:"
echo "  - Área Pública: http://localhost:8080"
echo "  - Área Admin: http://localhost:8080/admin"
echo ""

if [ "$AMBIENTE" == "producao" ]; then
    log_warning "ATENÇÃO - PRODUÇÃO:"
    echo "  1. Configure o firewall (portas 80, 443, 22)"
    echo "  2. Configure backups automáticos"
    echo "  3. Configure monitoramento"
    echo "  4. Teste HTTPS se configurado"
    echo ""
fi

log_info "Comandos úteis:"
echo "  - Ver logs: docker-compose -f $COMPOSE_FILE logs -f"
echo "  - Parar: docker-compose -f $COMPOSE_FILE down"
echo "  - Restart: docker-compose -f $COMPOSE_FILE restart"
echo "  - Backup: ./scripts/backup.sh"
echo ""

log_info "Para acessar o banco diretamente:"
echo "  docker exec -it tds-postgres psql -U semeadoruser -d semeadordb"
echo ""

log_success "Sistema pronto para uso!"