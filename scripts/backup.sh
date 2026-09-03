#!/bin/bash
# Simple PostgreSQL Backup Script for Tavro

BACKUP_DIR="/var/backups/tavro"
DB_NAME="${DB_DATABASE:-tavro}"
DB_USER="${DB_USERNAME:-tavro}"
DATE=$(date +"%Y%m%d_%H%M%S")

mkdir -p $BACKUP_DIR

echo "Starting backup of $DB_NAME..."
docker exec -t tavro-db-1 pg_dump -U $DB_USER $DB_NAME -F c > "$BACKUP_DIR/${DB_NAME}_${DATE}.dump"

if [ $? -eq 0 ]; then
  echo "Backup completed successfully: ${DB_NAME}_${DATE}.dump"
  
  # Remove backups older than 7 days
  find $BACKUP_DIR -name "${DB_NAME}_*.dump" -mtime +7 -exec rm {} \;
  echo "Cleaned up old backups."
else
  echo "Backup failed!"
  exit 1
fi
