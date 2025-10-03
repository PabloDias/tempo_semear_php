#!/bin/bash

# =============================================================================
# SCRIPT DE DEPLOY FINAL - SISTEMA TEMPO DE SEMEAR
# =============================================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[✓]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[!]${NC} $1"; }
log_error() { echo -e "${RED}[✗]${NC} $1"; }

AMBIENTE=${1:-desenvolvimento}

echo "=============================================="
echo "  DEPLOY - SISTEMA TEMPO DE SEMEAR"
echo "  Ambiente: $AMBIENTE"
echo "=============================================="
echo ""

if [[ "$AMBIENTE" != "desenvolvimento" && "$AMBIENTE" != "producao" ]]; then
    log_error "Ambiente inválido. Use: desenvolvimento ou producao"
    exit 1
fi

# Parar containers existentes
log_info "Parando containers antigos..."
docker-compose down 2>/dev/null || true
if [ -f docker-compose.prod.yml ]; then
    docker-compose -f docker-compose.prod.yml down 2>/dev/null || true
fi
log_success "Containers parados"

# Criar diretórios necessários no HOST
log_info "Criando diretórios no host..."
mkdir -p uploads backups logs/nginx ssl scripts

# Ajustar permissões (importante!)
log_info "Ajustando permissões dos diretórios..."
chmod 777 uploads backups  # Permissivo para desenvolvimento
log_success "Diretórios criados e permissões ajustadas"

# Criar arquivos .gitkeep
touch uploads/.gitkeep backups/.gitkeep logs/.gitkeep ssl/.gitkeep

# Verificar/criar .env
if [ ! -f .env ]; then
    log_warning "Arquivo .env não encontrado, criando..."
    cat > .env << 'EOF'
DB_PASSWORD=semearv2_2025
DB_HOST=db
DB_PORT=5432
DB_NAME=semeadordb
DB_USER=semeadoruser
EOF
    log_success "Arquivo .env criado"
fi

# Escolher arquivo docker-compose
if [ "$AMBIENTE" == "producao" ]; then
    COMPOSE_FILE="docker-compose.prod.yml"
    
    if [ ! -f "$COMPOSE_FILE" ]; then
        log_error "Arquivo $COMPOSE_FILE não encontrado!"
        exit 1
    fi
    
    # Verifica SSL em produção
    if [ ! -f ssl/cert.pem ] || [ ! -f ssl/key.pem ]; then
        log_warning "Certificados SSL não encontrados!"
        log_info "Configure SSL antes de ir para produção"
    fi
else
    COMPOSE_FILE="docker-compose.yml"
fi

# Build e start
log_info "Construindo e iniciando containers..."
docker-compose -f "$COMPOSE_FILE" up -d --build

log_info "Aguardando containers iniciarem (40 segundos)..."
sleep 40

# Verificar status
log_info "Verificando status dos containers..."
docker-compose -f "$COMPOSE_FILE" ps

# Testa PostgreSQL
log_info "Testando conexão com PostgreSQL..."
MAX_TRIES=30
TRIES=0

if [ "$AMBIENTE" == "producao" ]; then
    POSTGRES_CONTAINER="tds-postgres-prod"
    PHP_CONTAINER="tds-php-prod"
    NGINX_CONTAINER="tds-nginx-prod"
else
    POSTGRES_CONTAINER="tds-postgres"
    PHP_CONTAINER="tds-php"
    NGINX_CONTAINER="tds-nginx"
fi

until docker exec $POSTGRES_CONTAINER pg_isready -U semeadoruser -d semeadordb &>/dev/null || [ $TRIES -eq $MAX_TRIES ]; do
    sleep 1
    ((TRIES++))
    echo -n "."
done

if [ $TRIES -eq $MAX_TRIES ]; then
    log_error "PostgreSQL não respondeu no tempo esperado"
    log_info "Logs do PostgreSQL:"
    docker logs $POSTGRES_CONTAINER --tail 20
    exit 1
fi

log_success "PostgreSQL está pronto!"

# Testa PHP
log_info "Testando container PHP..."
if docker exec $PHP_CONTAINER php -v >/dev/null 2>&1; then
    log_success "PHP está funcionando"
    docker exec $PHP_CONTAINER php -v | head -1
else
    log_error "PHP com problemas!"
    log_info "Logs do PHP:"
    docker logs $PHP_CONTAINER
    exit 1
fi

# Verifica se precisa popular o banco
log_info "Verificando banco de dados..."
TABLES_EXIST=$(docker exec $POSTGRES_CONTAINER psql -U semeadoruser -d semeadordb -tAc "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public' AND table_name='cadastros'" 2>/dev/null || echo "0")

if [ "$TABLES_EXIST" == "0" ]; then
    log_info "Banco vazio. Executando migrações..."
    
    # Procura arquivos SQL
    for SQL_FILE in "01_create-tables-sql_cp.sql" "../01_create-tables-sql_cp.sql"; do
        if [ -f "$SQL_FILE" ]; then
            log_info "Criando tabelas..."
            docker cp "$SQL_FILE" $POSTGRES_CONTAINER:/tmp/01.sql
            docker exec $POSTGRES_CONTAINER psql -U semeadoruser -d semeadordb -f /tmp/01.sql
            log_success "Tabelas criadas"
            break
        fi
    done
    
    for SQL_FILE in "02_create-triggers-sql.sql" "../02_create-triggers-sql.sql"; do
        if [ -f "$SQL_FILE" ]; then
            log_info "Criando triggers..."
            docker cp "$SQL_FILE" $POSTGRES_CONTAINER:/tmp/02.sql
            docker exec $POSTGRES_CONTAINER psql -U semeadoruser -d semeadordb -f /tmp/02.sql
            log_success "Triggers criados"
            break
        fi
    done
    
    for SQL_FILE in "03_insert_states_cities.sql" "../03_insert_states_cities.sql"; do
        if [ -f "$SQL_FILE" ]; then
            log_info "Populando municípios..."
            docker cp "$SQL_FILE" $POSTGRES_CONTAINER:/tmp/03.sql
            docker exec $POSTGRES_CONTAINER psql -U semeadoruser -d semeadordb -f /tmp/03.sql
            log_success "217 municípios do MA inseridos"
            break
        fi
    done
else
    log_success "Banco de dados já inicializado"
fi

# Testa HTTP
log_info "Testando acesso HTTP..."
sleep 5

if [ "$AMBIENTE" == "producao" ]; then
    TEST_URL="http://localhost"
else
    TEST_URL="http://localhost:8080"
fi

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$TEST_URL" 2>/dev/null || echo "000")

if [ "$HTTP_CODE" == "200" ] || [ "$HTTP_CODE" == "302" ]; then
    log_success "Sistema respondendo (HTTP $HTTP_CODE)"
else
    log_warning "Sistema retornou HTTP $HTTP_CODE"
    log_info "Aguarde mais alguns segundos e teste manualmente"
fi

# Resumo final
echo ""
echo "=============================================="
log_success "DEPLOY CONCLUÍDO!"
echo "=============================================="
echo ""
log_info "URLs de Acesso:"
if [ "$AMBIENTE" == "producao" ]; then
    echo "  - Sistema: https://seu-dominio.com"
else
    echo "  - Área Pública: http://localhost:8080"
    echo "  - Cadastro: http://localhost:8080/cadastro.php"
    echo "  - Login: http://localhost:8080/login.php"
    echo "  - Admin: http://localhost:8080/admin"
fi
echo ""

log_info "Comandos úteis:"
echo "  - Ver logs PHP: docker logs -f $PHP_CONTAINER"
echo "  - Ver logs Nginx: docker logs -f $NGINX_CONTAINER"
echo "  - Ver logs DB: docker logs -f $POSTGRES_CONTAINER"
echo "  - Entrar no PHP: docker exec -it $PHP_CONTAINER sh"
echo "  - Parar tudo: docker-compose -f $COMPOSE_FILE down"
echo "  - Restart: docker-compose -f $COMPOSE_FILE restart"
echo ""

if [ "$AMBIENTE" == "desenvolvimento" ]; then
    log_info "Próximos passos:"
    echo "  1. Acesse: http://localhost:8080/cadastro.php"
    echo "  2. Crie uma conta de beneficiário"
    echo "  3. Faça login"
    echo "  4. Preencha o formulário"
    echo ""
    log_info "Para criar um usuário admin:"
    echo "  docker exec -it $PHP_CONTAINER php /var/www/scripts/criar_admin.php"
    echo ""
fi

log_success "Sistema pronto para uso!"