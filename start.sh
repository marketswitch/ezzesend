#!/bin/bash
echo "=== EzzeSend Starting ==="
echo "DB_HOST=$(grep DB_HOST .env)"

echo "Testing MySQL..."
php -r "
try {
    new PDO('mysql:host=hopper.proxy.rlwy.net;port=53871;dbname=railway;connect_timeout=10','root','dRHjLZTHTJBuvgyhRfFLYaFiOjYAttzy');
    echo 'MySQL OK' . PHP_EOL;
} catch(Exception \$e) { echo 'MySQL FAIL: '.\$e->getMessage().PHP_EOL; }
"

php artisan config:clear 2>/dev/null || true
php artisan migrate --force --no-interaction 2>&1 || echo "Migration warning"
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

echo "Starting on port ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
