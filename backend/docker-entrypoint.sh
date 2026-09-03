#!/bin/sh
set -e

# Run migrations automatically on boot (idempotent). Disable with SKIP_MIGRATIONS=1.
if [ "$SKIP_MIGRATIONS" != "1" ] && [ -n "$DB_HOST" ]; then
    echo "Ensuring database is reachable at ${DB_HOST}:${DB_PORT}..."
    until php -r "try{new PDO('pgsql:host='.\$argv[1].';port='.\$argv[2].';dbname='.\$argv[3].';user='.\$argv[4].';password='.\$argv[5]);}catch(Exception \$e){exit(1);}" \
        "$DB_HOST" "$DB_PORT" "$DB_DATABASE" "$DB_USERNAME" "$DB_PASSWORD"; do
        echo "Database not ready, retrying in 2s..."
        sleep 2
    done
    echo "Database ready. Running migrations..."
    php artisan migrate --force
fi

PORT="${PORT:-8080}"

case "$PROCESS_TYPE" in
    reverb)
        echo "Starting Laravel Reverb WebSocket server on 0.0.0.0:${PORT}..."
        exec php artisan reverb:start --host=0.0.0.0 --port="$PORT"
        ;;
    worker)
        echo "Starting queue worker..."
        exec php artisan queue:work --sleep=3 --tries=3
        ;;
    *)
        echo "Starting web server on 0.0.0.0:${PORT}..."
        exec php artisan serve --host=0.0.0.0 --port="$PORT"
        ;;
esac
