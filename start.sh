#!/bin/bash
echo "=== EzzeSend Starting ==="

php -r "
try {
    \$pdo = new PDO('mysql:host=hopper.proxy.rlwy.net;port=53871;dbname=railway;connect_timeout=10','root','dRHjLZTHTJBuvgyhRfFLYaFiOjYAttzy');
    echo 'DB connected OK' . PHP_EOL;
} catch(Exception \$e) {
    echo 'DB FAIL: '.\$e->getMessage().PHP_EOL;
    exit(1);
}
"

php artisan migrate --force --no-interaction 2>&1
php artisan db:seed --force --no-interaction 2>&1 || true
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

echo "Starting on port ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
