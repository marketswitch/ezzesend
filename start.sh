#!/bin/bash
echo "=== EzzeSend Starting ==="

# Try private networking first, fall back to checking if it resolves
MYSQL_HOST="mysql.railway.internal"
MYSQL_PORT="3306"

# Test DNS resolution
php -r "
\$host = 'mysql.railway.internal';
\$ip = gethostbyname(\$host);
echo 'DNS lookup: ' . \$host . ' -> ' . \$ip . PHP_EOL;
if (\$ip === \$host) {
    echo 'DNS FAILED - trying TCP proxy' . PHP_EOL;
    exit(1);
} else {
    echo 'DNS OK' . PHP_EOL;
    exit(0);
}
" || MYSQL_HOST="${MYSQLHOST:-mysql.railway.internal}"

echo "Using DB_HOST: $MYSQL_HOST"

cat > .env << ENVEOF
APP_NAME=EzzeSend
APP_ENV=production
APP_KEY=base64:X3pLm8vQpR2wNjY4tBdCgUaEiHsSoF6nDeKuMxIcAo=
APP_DEBUG=true
APP_URL=https://ezzesend-production.up.railway.app

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=${MYSQL_HOST}
DB_PORT=${MYSQL_PORT}
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

echo "DB_HOST in .env: $(grep ^DB_HOST .env)"

# Wait for MySQL
echo "Waiting for MySQL to be ready..."
for i in $(seq 1 30); do
    php -r "
    \$pdo = new PDO(
        'mysql:host=${MYSQL_HOST};port=${MYSQL_PORT};dbname=railway',
        'root',
        'dRHjLZTHTJBuvgyhRfFLYaFiOjYAttzy',
        [PDO::ATTR_TIMEOUT => 3]
    );
    echo 'MySQL connected OK' . PHP_EOL;
    " 2>/dev/null && { echo "MySQL ready!"; break; }
    echo "Attempt $i - waiting..."
    sleep 2
done

php artisan config:clear 2>/dev/null || true
php artisan migrate --force --no-interaction 2>&1 || echo "Migration warning"
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

echo "Starting on port ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
