#!/bin/bash
echo "=== EzzeSend Starting ==="
echo "PORT=$PORT APP_ENV=$APP_ENV"
echo "MYSQL_HOST=$MYSQL_HOST MYSQL_PORT=$MYSQL_PORT"

# Write .env fresh every time
cat > /var/www/html/.env << ENVEOF
APP_NAME=EzzeSend
APP_ENV=production
APP_KEY=base64:X3pLm8vQpR2wNjY4tBdCgUaEiHsSoF6nDeKuMxIcAo=
APP_DEBUG=true
APP_URL=https://ezzesend-production.up.railway.app
LOG_CHANNEL=stack
LOG_LEVEL=debug
DB_CONNECTION=mysql
DB_HOST=${MYSQL_HOST:-mysql.railway.internal}
DB_PORT=${MYSQL_PORT:-3306}
DB_DATABASE=${MYSQL_DATABASE:-railway}
DB_USERNAME=${MYSQL_USER:-root}
DB_PASSWORD=${MYSQL_PASSWORD:-dRHjLZTHTJBuvgyhRfFLYaFiOjYAttzy}
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
BROADCAST_DRIVER=log
FILESYSTEM_DISK=local
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1
ENVEOF

echo "DB_HOST=$(grep DB_HOST /var/www/html/.env)"

# Test connection
php -r "
\$h = getenv('MYSQL_HOST') ?: 'mysql.railway.internal';
\$p = getenv('MYSQL_PORT') ?: '3306';
\$d = getenv('MYSQL_DATABASE') ?: 'railway';
\$u = getenv('MYSQL_USER') ?: 'root';
\$pw = getenv('MYSQL_PASSWORD') ?: 'dRHjLZTHTJBuvgyhRfFLYaFiOjYAttzy';
echo \"Connecting to \$h:\$p/\$d as \$u\n\";
try {
    new PDO(\"mysql:host=\$h;port=\$p;dbname=\$d;connect_timeout=10\", \$u, \$pw);
    echo \"DB OK\n\";
} catch(Exception \$e) { echo \"DB FAIL: \".\$e->getMessage().\"\n\"; }
"

cd /var/www/html
php artisan config:clear 2>/dev/null || true
php artisan migrate --force --no-interaction 2>&1 || echo "Migration warning"
php artisan db:seed --force --no-interaction 2>&1 || true
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

echo "Starting server on ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
