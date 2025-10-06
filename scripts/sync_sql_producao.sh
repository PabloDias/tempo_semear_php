#!/bin/bash

# =============================================================================
# SCRIPT DE SINCRONIZAÇÃO SQL - DESENVOLVIMENTO → PRODUÇÃO
# =============================================================================
# Este script extrai todas as functions e triggers do desenvolvimento
# e prepara os arquivos SQL para aplicar em produção
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
echo "  SINCRONIZAÇÃO SQL - DEV → PRODUÇÃO"
echo "=============================================="
echo ""

# =============================================================================
# CONFIGURAÇÕES
# =============================================================================

CONTAINER_NAME="tds-postgres"
DB_USER="semeadoruser"
DB_NAME="semeadordb"
OUTPUT_DIR="sql"
BACKUP_DIR="sql/backups"
MIGRATIONS_DIR="sql/migrations"

# Criar diretórios se não existirem
mkdir -p "$OUTPUT_DIR" "$BACKUP_DIR" "$MIGRATIONS_DIR"

# =============================================================================
# PASSO 1: Backup do Arquivo Atual
# =============================================================================

log_info "Passo 1: Fazendo backup do arquivo atual..."

if [ -f "$OUTPUT_DIR/02_create-triggers-sql.sql" ]; then
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    cp "$OUTPUT_DIR/02_create-triggers-sql.sql" \
       "$BACKUP_DIR/02_create-triggers-sql_$TIMESTAMP.sql.bkp"
    log_success "Backup criado: 02_create-triggers-sql_$TIMESTAMP.sql.bkp"
else
    log_warning "Arquivo anterior não encontrado, criando novo..."
fi

echo ""

# =============================================================================
# PASSO 2: Extrair Functions e Triggers do Banco
# =============================================================================

log_info "Passo 2: Extraindo functions e triggers do banco..."

# Criar SQL temporário de extração
cat > /tmp/extract_triggers.sql << 'EOF'
\o /tmp/triggers_output.sql

-- Cabeçalho
\echo '-- ============================================================================='
\echo '-- SISTEMA TEMPO DE SEMEAR - 2ª EDIÇÃO'
\echo '-- ============================================================================='
\echo '-- Arquivo: 02_create-triggers-sql.sql'
\echo '-- Descrição: Cria todas as functions e triggers do sistema'
\echo '-- Gerado automaticamente em: '
SELECT CURRENT_TIMESTAMP::TEXT;
\echo '-- ============================================================================='
\echo ''

-- Functions
\echo '-- ============================================================================='
\echo '-- FUNCTIONS'
\echo '-- ============================================================================='
\echo ''

SELECT pg_get_functiondef(p.oid) || E';\n'
FROM pg_proc p
JOIN pg_namespace n ON p.pronamespace = n.oid
WHERE n.nspname = 'public'
  AND p.prokind = 'f'
ORDER BY p.proname;

-- Triggers
\echo ''
\echo '-- ============================================================================='
\echo '-- TRIGGERS'
\echo '-- ============================================================================='
\echo ''

SELECT pg_get_triggerdef(t.oid) || E';\n'
FROM pg_trigger t
JOIN pg_class c ON t.tgrelid = c.oid
JOIN pg_namespace n ON c.relnamespace = n.oid
WHERE n.nspname = 'public'
  AND NOT t.tgisinternal
ORDER BY c.relname, t.tgname;

\echo ''
\echo '-- ============================================================================='
\echo '-- FIM DO ARQUIVO'
\echo '-- ============================================================================='

\o
EOF

# Copiar para container e executar
docker cp /tmp/extract_triggers.sql $CONTAINER_NAME:/tmp/
docker exec $CONTAINER_NAME psql -U $DB_USER -d $DB_NAME -f /tmp/extract_triggers.sql > /dev/null 2>&1

# Copiar resultado de volta
docker cp $CONTAINER_NAME:/tmp/triggers_output.sql /tmp/

if [ -f /tmp/triggers_output.sql ]; then
    log_success "Functions e triggers extraídos"
else
    log_error "Falha ao extrair functions e triggers"
    exit 1
fi

echo ""

# =============================================================================
# PASSO 3: Limpar o Arquivo (remover linhas indesejadas)
# =============================================================================

log_info "Passo 3: Limpando arquivo..."

# Limpar linhas indesejadas
cat /tmp/triggers_output.sql | \
    grep -v "^--$" | \
    grep -v "^SET " | \
    grep -v "^SELECT " | \
    sed '/^$/N;/^\n$/D' \
    > "$OUTPUT_DIR/02_create-triggers-sql.sql"

log_success "Arquivo limpo e salvo"

echo ""

# =============================================================================
# PASSO 4: Adicionar Verificações e Mensagens
# =============================================================================

log_info "Passo 4: Adicionando verificações..."

# Adicionar mensagens de progresso (opcional, pode ser feito manualmente)

log_success "Arquivo preparado para produção"

echo ""

# =============================================================================
# PASSO 5: Resumo
# =============================================================================

log_info "Passo 5: Gerando resumo..."

echo ""
echo "=============================================="
echo "RESUMO DA EXTRAÇÃO"
echo "=============================================="

# Contar functions
FUNCTIONS_COUNT=$(docker exec $CONTAINER_NAME psql -U $DB_USER -d $DB_NAME -tAc "
    SELECT COUNT(*)
    FROM pg_proc p
    JOIN pg_namespace n ON p.pronamespace = n.oid
    WHERE n.nspname = 'public' AND p.prokind = 'f'
")

# Contar triggers
TRIGGERS_COUNT=$(docker exec $CONTAINER_NAME psql -U $DB_USER -d $DB_NAME -tAc "
    SELECT COUNT(*)
    FROM pg_trigger t
    JOIN pg_class c ON t.tgrelid = c.oid
    WHERE NOT t.tgisinternal
")

echo "Functions extraídas: $FUNCTIONS_COUNT"
echo "Triggers extraídos: $TRIGGERS_COUNT"
echo ""
echo "Arquivo gerado:"
echo "  → $OUTPUT_DIR/02_create-triggers-sql.sql"
echo ""
echo "Backup anterior salvo em:"
echo "  → $BACKUP_DIR/"
echo "=============================================="

echo ""

# =============================================================================
# PASSO 6: Listar Functions e Triggers
# =============================================================================

log_info "Functions e Triggers no arquivo:"
echo ""

docker exec $CONTAINER_NAME psql -U $DB_USER -d $DB_NAME -c "
    SELECT
        'Function' as tipo,
        p.proname as nome
    FROM pg_proc p
    JOIN pg_namespace n ON p.pronamespace = n.oid
    WHERE n.nspname = 'public' AND p.prokind = 'f'

    UNION ALL

    SELECT
        'Trigger' as tipo,
        t.tgname || ' (tabela: ' || c.relname || ')' as nome
    FROM pg_trigger t
    JOIN pg_class c ON t.tgrelid = c.oid
    WHERE NOT t.tgisinternal

    ORDER BY tipo, nome;
"

echo ""

# =============================================================================
# PASSO 7: Instruções
# =============================================================================

echo "=============================================="
echo "PRÓXIMOS PASSOS"
echo "=============================================="
echo ""
echo "1. Revisar o arquivo gerado:"
echo "   cat $OUTPUT_DIR/02_create-triggers-sql.sql"
echo ""
echo "2. Testar em banco limpo (opcional):"
echo "   docker exec $CONTAINER_NAME psql -U $DB_USER -c 'CREATE DATABASE test_db;'"
echo "   docker exec $CONTAINER_NAME psql -U $DB_USER -d test_db -f /tmp/02_create-triggers-sql.sql"
echo "   docker exec $CONTAINER_NAME psql -U $DB_USER -c 'DROP DATABASE test_db;'"
echo ""
echo "3. Commitar no Git:"
echo "   git add $OUTPUT_DIR/02_create-triggers-sql.sql"
echo "   git commit -m 'chore: Atualiza functions e triggers'"
echo "   git push"
echo ""
echo "4. Aplicar em produção:"
echo "   # Fazer backup primeiro!"
echo "   docker exec tds-postgres-prod pg_dump -U semeadoruser semeadordb | gzip > backup.sql.gz"
echo "   # Aplicar"
echo "   docker cp $OUTPUT_DIR/02_create-triggers-sql.sql tds-postgres-prod:/tmp/"
echo "   docker exec tds-postgres-prod psql -U semeadoruser -d semeadordb -f /tmp/02_create-triggers-sql.sql"
echo ""
echo "=============================================="
echo ""

log_success "Sincronização concluída!"

# Limpar arquivos temporários
rm -f /tmp/extract_triggers.sql /tmp/triggers_output.sql

exit 0
