#!/bin/bash

# Verifica se os containers estão rodando
if ! docker ps | grep -q "tds-postgres-prod"; then
    echo "ALERTA: Container PostgreSQL parado!"
    # Enviar e-mail ou notificação
fi

if ! docker ps | grep -q "tds-nginx-prod"; then
    echo "ALERTA: Container Nginx parado!"
    # Enviar e-mail ou notificação
fi

# Verifica espaço em disco
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "ALERTA: Espaço em disco acima de 80%"
fi

# Verifica logs de erro
ERROR_COUNT=$(docker logs tds-nginx-prod 2>&1 | grep -i "error" | wc -l)
if [ $ERROR_COUNT -gt 100 ]; then
    echo "ALERTA: Muitos erros no Nginx ($ERROR_COUNT)"
fi