#!/bin/bash

# Configurações
BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="semeadordb"
DB_USER="semeadoruser"
RETENTION_DAYS=30

# Cria diretório se não existir
mkdir -p $BACKUP_DIR

# Backup do banco de dados
docker exec tds-postgres-prod pg_dump -U $DB_USER $DB_NAME | gzip > "$BACKUP_DIR/db_backup_$DATE.sql.gz"

# Backup dos uploads
tar -czf "$BACKUP_DIR/uploads_backup_$DATE.tar.gz" ./uploads

# Remove backups antigos
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "uploads_backup_*.tar.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup realizado: $DATE"