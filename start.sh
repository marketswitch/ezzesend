#!/bin/bash
echo "=== EzzeSend Starting ==="

# Test DNS for mysql.railway.internal
DNS_RESULT=$(php -r "echo gethostbyname('mysql.railway.internal');" 2>/dev/null)
echo "DNS mysql.railway.internal = $DNS_RESULT"

# If DNS failed (returns the hostname unchanged), use TCP proxy
if [ "$DNS_RESULT" = "mysql.railway.internal" ] || [ -z "$DNS_RESULT" ]; then
    echo "Private DNS failed — checking for TCP proxy vars"
    # Use MYSQLHOST which Railway auto-sets from the MySQL service
    MYSQL_HOST="${MYSQLHOST:-mysql.railway.internal}"
    MYSQL_PORT="${MYSQLPORT:-3306}"
else
    echo "Private DNS OK"
    MYSQL_HOST="mysql.railway.internal"
    MYSQL_PORT="3306"
fi

echo "Using MYSQL_HOST=$MYSQL_HOST PORT=$MYSQL_PORT"

cat > .env << ENVEOF
APP_NAME=EzzeSend
APP_ENV=production
APP_KEY=base64:X3pLm8vQpR2wNjY4tBdCgUaEiHsSoF6nDeKuMxIcAo=
APP_DEBUG=true
APP_URL=https://ezzesend-production.up.railway.app

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=$MYSQL_HOST
DB_PORT=$MYSQL_PORT
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

echo "Written .env with DB_HOST=$MYSQL_HOST"

# Wait for MySQL with timeout
echo "Waiting for MySQL..."
for i in $(seq 1 20); do
    result=$(php -r "
    try {
        new PDO('mysql:host=$MYSQL_HOST;port=$MYSQL_PORT;dbname=railway;connect_timeout=3', 'root', 'dRHjLZTHTJBuvgyhRfFLYaFiOjYAttzy');
        echo 'OK';
    } catch(Exception \$e) { echo 'FAIL:'.\$e->getMessage(); }
    " 2>/dev/null)
    echo "Attempt $i: $result"
    if [ "$result" = "OK" ]; then
        echo "MySQL connected!"
        break
    fi
    sleep 2
done

php artisan config:clear 2>/dev/null || true
php artisan migrate --force --no-interaction 2>&1 || echo "Migration warning"
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

echo "Starting on port ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
