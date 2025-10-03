#!/bin/bash

# =============================================================================
# SCRIPT DE DEPLOY CORRIGIDO - SISTEMA TEMPO DE SEMEAR
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

echo "=============================================="
echo "  DEPLOY - SISTEMA TEMPO DE SEMEAR"
echo "=============================================="
echo ""

# Para containers existentes
log_info "Parando containers antigos..."
docker-compose down 2>/dev/null || true

# Remove diretórios com problemas de permissão
log_info "Limpando diretórios..."
sudo rm -rf uploads backups logs 2>/dev/null || true

# Recria diretórios com permissões corretas
log_info "Criando diretórios..."
mkdir -p uploads backups logs/nginx ssl

# Define permissões corretas ANTES do Docker
log_info "Ajustando permissões..."
chmod 777 uploads backups  # Temporário para desenvolvimento

# Cria .gitkeep
touch uploads/.gitkeep backups/.gitkeep logs/.gitkeep ssl/.gitkeep

# Verifica .env
if [ ! -f .env ]; then
    log_warning "Criando arquivo .env de desenvolvimento..."
    cat > .env << 'EOF'
DB_PASSWORD=dev_senha_123
DB_HOST=db
DB_PORT=5432
DB_NAME=semeadordb
DB_USER=semeadoruser
EOF
fi

# Build e start
log_info "Iniciando containers..."
docker-compose up -d --build

# Aguarda PostgreSQL
log_info "Aguardando PostgreSQL (30s)..."
sleep 30

# Verifica se PostgreSQL está pronto
log_info "Testando conexão com PostgreSQL..."
docker exec tds-postgres pg_isready -U semeadoruser -d semeadordb || {
    log_error "PostgreSQL não está respondendo!"
    log_info "Mostrando logs do PostgreSQL:"
    docker logs tds-postgres
    exit 1
}

log_success "PostgreSQL pronto!"

# Ajusta permissões do uploads DENTRO do container
log_info "Ajustando permissões do uploads no container..."
docker exec tds-php chown -R www-data:www-data /var/www/uploads 2>/dev/null || true

# Verifica se tabelas existem
log_info "Verificando banco de dados..."
TABLES_EXIST=$(docker exec tds-postgres psql -U semeadoruser -d semeadordb -tAc "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public' AND table_name='cadastros'" 2>/dev/null || echo "0")

if [ "$TABLES_EXIST" == "0" ]; then
    log_info "Executando migrações..."
    
    # Procura arquivos SQL no diretório atual e no pai
    for SQL_FILE in "01_create-tables-sql_cp.sql" "../01_create-tables-sql_cp.sql"; do
        if [ -f "$SQL_FILE" ]; then
            log_info "Encontrado: $SQL_FILE"
            docker cp "$SQL_FILE" tds-postgres:/tmp/01.sql
            docker exec tds-postgres psql -U semeadoruser -d semeadordb -f /tmp/01.sql
            log_success "Tabelas criadas"
            break
        fi
    done
    
    for SQL_FILE in "02_create-triggers-sql.sql" "../02_create-triggers-sql.sql"; do
        if [ -f "$SQL_FILE" ]; then
            log_info "Encontrado: $SQL_FILE"
            docker cp "$SQL_FILE" tds-postgres:/tmp/02.sql
            docker exec tds-postgres psql -U semeadoruser -d semeadordb -f /tmp/02.sql
            log_success "Triggers criados"
            break
        fi
    done
    
    for SQL_FILE in "03_insert_states_cities.sql" "../03_insert_states_cities.sql"; do
        if [ -f "$SQL_FILE" ]; then
            log_info "Encontrado: $SQL_FILE"
            docker cp "$SQL_FILE" tds-postgres:/tmp/03.sql
            docker exec tds-postgres psql -U semeadoruser -d semeadordb -f /tmp/03.sql
            log_success "Municípios populados"
            break
        fi
    done
else
    log_success "Banco já inicializado"
fi

# Mostra status
log_info "Status dos containers:"
docker-compose ps

# Testa PHP
log_info "Testando container PHP..."
docker exec tds-php php -v || {
    log_error "Container PHP com problemas!"
    log_info "Logs do PHP:"
    docker logs tds-php
    exit 1
}

log_success "Container PHP OK"

# Testa Nginx
log_info "Testando Nginx..."
sleep 5
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080 2>/dev/null || echo "000")

if [ "$HTTP_CODE" == "200" ] || [ "$HTTP_CODE" == "302" ]; then
    log_success "Nginx respondendo (HTTP $HTTP_CODE)"
else
    log_warning "Nginx retornou: HTTP $HTTP_CODE"
    log_info "Logs do Nginx:"
    docker logs tds-nginx --tail 20
    log_info "Logs do PHP:"
    docker logs tds-php --tail 20
fi

echo ""
echo "=============================================="
log_success "DEPLOY CONCLUÍDO!"
echo "=============================================="
echo ""
log_info "URLs:"
echo "  - Área Pública: http://localhost:8080"
echo "  - Cadastro: http://localhost:8080/cadastro.php"
echo "  - Login: http://localhost:8080/login.php"
echo "  - Admin: http://localhost:8080/admin"
echo ""
log_info "Comandos úteis:"
echo "  - Ver logs PHP: docker logs -f tds-php"
echo "  - Ver logs Nginx: docker logs -f tds-nginx"
echo "  - Ver logs DB: docker logs -f tds-postgres"
echo "  - Entrar no container: docker exec -it tds-php sh"
echo "  - Parar tudo: docker-compose down"
echo ""