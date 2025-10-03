#!/bin/bash

# =============================================================================
# SCRIPT DE DIAGNÓSTICO - SISTEMA TEMPO DE SEMEAR
# =============================================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo "=============================================="
echo "  DIAGNÓSTICO DO SISTEMA"
echo "=============================================="
echo ""

# 1. Status dos Containers
echo -e "${BLUE}1. STATUS DOS CONTAINERS${NC}"
echo "-------------------------------------------"
docker ps -a --filter "name=tds" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""

# 2. Logs do PHP (últimas 30 linhas)
echo -e "${BLUE}2. LOGS DO PHP (últimas 30 linhas)${NC}"
echo "-------------------------------------------"
docker logs tds-php --tail 30 2>&1
echo ""

# 3. Logs do Nginx (últimas 20 linhas)
echo -e "${BLUE}3. LOGS DO NGINX (últimas 20 linhas)${NC}"
echo "-------------------------------------------"
docker logs tds-nginx --tail 20 2>&1
echo ""

# 4. Logs do PostgreSQL (últimas 20 linhas)
echo -e "${BLUE}4. LOGS DO POSTGRESQL (últimas 20 linhas)${NC}"
echo "-------------------------------------------"
docker logs tds-postgres --tail 20 2>&1
echo ""

# 5. Testa conexão PHP-FPM
echo -e "${BLUE}5. TESTE DO PHP-FPM${NC}"
echo "-------------------------------------------"
if docker exec tds-php php -v >/dev/null 2>&1; then
    echo -e "${GREEN}✓ PHP está funcionando${NC}"
    docker exec tds-php php -v | head -1
else
    echo -e "${RED}✗ PHP com problemas${NC}"
fi
echo ""

# 6. Testa conexão com banco
echo -e "${BLUE}6. TESTE DE CONEXÃO COM BANCO${NC}"
echo "-------------------------------------------"
if docker exec tds-postgres pg_isready -U semeadoruser -d semeadordb >/dev/null 2>&1; then
    echo -e "${GREEN}✓ PostgreSQL está aceitando conexões${NC}"
else
    echo -e "${RED}✗ PostgreSQL não está respondendo${NC}"
fi
echo ""

# 7. Verifica arquivos PHP
echo -e "${BLUE}7. ARQUIVOS PHP PRINCIPAIS${NC}"
echo "-------------------------------------------"
for file in index.php cadastro.php login.php dashboard.php db.php; do
    if docker exec tds-php test -f "/var/www/html/$file" 2>/dev/null; then
        SIZE=$(docker exec tds-php stat -c%s "/var/www/html/$file" 2>/dev/null)
        echo -e "${GREEN}✓${NC} $file ($SIZE bytes)"
    else
        echo -e "${RED}✗${NC} $file (não encontrado)"
    fi
done
echo ""

# 8. Permissões do uploads
echo -e "${BLUE}8. PERMISSÕES DO DIRETÓRIO UPLOADS${NC}"
echo "-------------------------------------------"
docker exec tds-php ls -la /var/www/ 2>/dev/null | grep uploads || echo "Diretório não encontrado"
echo ""

# 9. Verifica vendor (Composer)
echo -e "${BLUE}9. DEPENDÊNCIAS DO COMPOSER${NC}"
echo "-------------------------------------------"
if docker exec tds-php test -d "/var/www/html/vendor" 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Vendor instalado"
    docker exec tds-php ls /var/www/html/vendor/phpoffice 2>/dev/null | head -5
else
    echo -e "${RED}✗${NC} Vendor não encontrado"
fi
echo ""

# 10. Teste HTTP
echo -e "${BLUE}10. TESTE HTTP${NC}"
echo "-------------------------------------------"
for path in "" "/cadastro.php" "/login.php" "/admin"; do
    CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:8080$path" 2>/dev/null || echo "000")
    if [ "$CODE" == "200" ] || [ "$CODE" == "302" ]; then
        echo -e "${GREEN}✓${NC} http://localhost:8080$path → HTTP $CODE"
    else
        echo -e "${RED}✗${NC} http://localhost:8080$path → HTTP $CODE"
    fi
done
echo ""

# 11. Portas em uso
echo -e "${BLUE}11. PORTAS EM USO${NC}"
echo "-------------------------------------------"
netstat -tlnp 2>/dev/null | grep -E ":(8080|5433)" || ss -tlnp 2>/dev/null | grep -E ":(8080|5433)" || echo "Comando netstat/ss não disponível"
echo ""

# 12. Espaço em disco
echo -e "${BLUE}12. ESPAÇO EM DISCO${NC}"
echo "-------------------------------------------"
df -h . | tail -1
echo ""

# 13. Resumo
echo "=============================================="
echo -e "${BLUE}RESUMO DO DIAGNÓSTICO${NC}"
echo "=============================================="

PROBLEMS=0

# Verifica containers
if ! docker ps --filter "name=tds-php" --filter "status=running" | grep -q tds-php; then
    echo -e "${RED}✗ Container PHP não está rodando${NC}"
    PROBLEMS=$((PROBLEMS+1))
fi

if ! docker ps --filter "name=tds-nginx" --filter "status=running" | grep -q tds-nginx; then
    echo -e "${RED}✗ Container Nginx não está rodando${NC}"
    PROBLEMS=$((PROBLEMS+1))
fi

if ! docker ps --filter "name=tds-postgres" --filter "status=running" | grep -q tds-postgres; then
    echo -e "${RED}✗ Container PostgreSQL não está rodando${NC}"
    PROBLEMS=$((PROBLEMS+1))
fi

if [ $PROBLEMS -eq 0 ]; then
    echo -e "${GREEN}✓ Todos os containers estão rodando${NC}"
    echo ""
    echo "Se o sistema ainda não está funcionando:"
    echo "1. Verifique os logs acima"
    echo "2. Execute: docker-compose restart"
    echo "3. Aguarde 30 segundos e teste novamente"
else
    echo ""
    echo -e "${RED}PROBLEMAS DETECTADOS!${NC}"
    echo ""
    echo "Soluções sugeridas:"
    echo "1. Parar tudo: docker-compose down"
    echo "2. Limpar volumes: docker volume prune -f"
    echo "3. Executar: ./scripts/fix_and_deploy.sh"
fi

echo ""