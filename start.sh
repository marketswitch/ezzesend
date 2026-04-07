#!/bin/bash
echo "=== EzzeSend Starting ==="

# Hardcode DB connection directly - bypasses Railway variable injection issues
# Using Railway's private networking: servicename.railway.internal

cat > .env << 'ENVEOF'
APP_NAME=EzzeSend
APP_ENV=production
APP_KEY=base64:X3pLm8vQpR2wNjY4tBdCgUaEiHsSoF6nDeKuMxIcAo=
APP_DEBUG=true
APP_URL=https://ezzesend-production.up.railway.app

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=mysql.railway.internal
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=dRHjLZTHTJBuvgyhRfFLYaFiOjYAttzy

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
BROADCAST_DRIVER=log
FILESYSTEM_DISK=local

BROADCAST_DRIVER=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1
ENVEOF

echo "DB_HOST=$(grep DB_HOST .env)"

# Wait for MySQL to be ready (up to 30 seconds)
echo "Waiting for MySQL..."
for i in $(seq 1 30); do
    php -r "
    try {
        \$pdo = new PDO('mysql:host=mysql.railway.internal;port=3306;dbname=railway', 'root', 'dRHjLZTHTJBuvgyhRfFLYaFiOjYAttzy');
        echo 'MySQL connected!' . PHP_EOL;
        exit(0);
    } catch(Exception \$e) {
        echo 'Waiting... ' . \$e->getMessage() . PHP_EOL;
        exit(1);
    }
    " && break
    sleep 1
done

php artisan config:clear 2>/dev/null || true
php artisan migrate --force --no-interaction 2>&1 || echo "Migration warning"
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

echo "Starting on port ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
