#!/bin/bash
echo "=== EzzeSend Start ==="
echo "MYSQL_HOST=$MYSQL_HOST | MYSQLHOST=$MYSQLHOST | DB_HOST=$DB_HOST"

# Resolve DB host from any Railway variable name
H="${MYSQL_HOST:-${MYSQLHOST:-mysql.railway.internal}}"
P="${MYSQL_PORT:-${MYSQLPORT:-3306}}"
D="${MYSQL_DATABASE:-${MYSQLDATABASE:-railway}}"
U="${MYSQL_USER:-${MYSQLUSER:-root}}"
W="${MYSQL_PASSWORD:-${MYSQLPASSWORD:-${MYSQL_ROOT_PASSWORD:-dRHjLZTHTJBuvgyhRfFLYaFiOjYAttzy}}}"

echo "Using DB: $H:$P/$D as $U"

cat > /var/www/html/.env << ENVEOF
APP_NAME=EzzeSend
APP_ENV=production
APP_KEY=base64:X3pLm8vQpR2wNjY4tBdCgUaEiHsSoF6nDeKuMxIcAo=
APP_DEBUG=true
APP_URL=https://ezzesend-production.up.railway.app
LOG_CHANNEL=stack
LOG_LEVEL=debug
DB_CONNECTION=mysql
DB_HOST=$H
DB_PORT=$P
DB_DATABASE=$D
DB_USERNAME=$U
DB_PASSWORD=$W
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

php artisan config:clear 2>/dev/null || true
php artisan migrate --force --no-interaction 2>&1 || echo "Migration warning"
php artisan db:seed --force --no-interaction 2>&1 || true
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

echo "Starting on ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
