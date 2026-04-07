#!/bin/bash
echo "=== EzzeSend Starting ==="
echo "=== ALL ENVIRONMENT VARIABLES ==="
env | grep -i "mysql\|db_\|database\|railway\|port\|host" | sort
echo "=== END ENV ==="

# Try to get DB values from multiple possible Railway variable names
DB_HOST_RESOLVED="${DB_HOST:-${MYSQLHOST:-${RAILWAY_PRIVATE_DOMAIN:-mysql.railway.internal}}}"
DB_PORT_RESOLVED="${DB_PORT:-${MYSQLPORT:-3306}}"
DB_NAME_RESOLVED="${DB_DATABASE:-${MYSQLDATABASE:-${MYSQL_DATABASE:-railway}}}"
DB_USER_RESOLVED="${DB_USERNAME:-${MYSQLUSER:-root}}"
DB_PASS_RESOLVED="${DB_PASSWORD:-${MYSQLPASSWORD:-${MYSQL_ROOT_PASSWORD}}}"

echo "=== RESOLVED DB VALUES ==="
echo "HOST: $DB_HOST_RESOLVED"
echo "PORT: $DB_PORT_RESOLVED"
echo "NAME: $DB_NAME_RESOLVED"
echo "USER: $DB_USER_RESOLVED"
echo "PASS: [hidden]"

# Write .env with resolved values
cat > .env << ENVFILE
APP_NAME=${APP_NAME:-EzzeSend}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY:-base64:X3pLm8vQpR2wNjY4tBdCgUaEiHsSoF6nDeKuMxIcAo=}
APP_DEBUG=${APP_DEBUG:-true}
APP_URL=${APP_URL:-https://ezzesend-production.up.railway.app}

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=${DB_HOST_RESOLVED}
DB_PORT=${DB_PORT_RESOLVED}
DB_DATABASE=${DB_NAME_RESOLVED}
DB_USERNAME=${DB_USER_RESOLVED}
DB_PASSWORD=${DB_PASS_RESOLVED}

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
BROADCAST_DRIVER=log
FILESYSTEM_DISK=local

PUSHER_APP_ID=${PUSHER_APP_ID}
PUSHER_APP_KEY=${PUSHER_APP_KEY}
PUSHER_APP_SECRET=${PUSHER_APP_SECRET}
PUSHER_APP_CLUSTER=${PUSHER_APP_CLUSTER:-mt1}
ENVFILE

echo "=== .env DB_HOST line ==="
grep DB_HOST .env

php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

echo "Running migrations..."
php artisan migrate --force --no-interaction 2>&1 || echo "Migration warning — continuing"

php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

echo "Starting server on port ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
