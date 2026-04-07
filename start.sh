#!/bin/bash
echo "=== EzzeSend Starting ==="

cat > .env << 'ENVEOF'
APP_NAME=EzzeSend
APP_ENV=production
APP_KEY=base64:X3pLm8vQpR2wNjY4tBdCgUaEiHsSoF6nDeKuMxIcAo=
APP_DEBUG=true
APP_URL=https://ezzesend-production.up.railway.app

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=hopper.proxy.rlwy.net
DB_PORT=53871
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=dRHjLZTHTJBuvgyhRfFLYaFiOjYAttzy

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

echo "Testing MySQL connection to hopper.proxy.rlwy.net:53871..."
php -r "
try {
    \$pdo = new PDO('mysql:host=hopper.proxy.rlwy.net;port=53871;dbname=railway;connect_timeout=10', 'root', 'dRHjLZTHTJBuvgyhRfFLYaFiOjYAttzy');
    echo 'MySQL connected OK!' . PHP_EOL;
} catch(Exception \$e) {
    echo 'MySQL error: ' . \$e->getMessage() . PHP_EOL;
}
"

php artisan config:clear 2>/dev/null || true
php artisan migrate --force --no-interaction 2>&1 || echo "Migration warning"
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

echo "Starting on port ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
