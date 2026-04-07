#!/bin/bash
echo "=== EzzeSend Starting ==="

# Test DB first
php -r "
try {
    \$pdo = new PDO('mysql:host=hopper.proxy.rlwy.net;port=53871;dbname=railway;connect_timeout=10', 'root', 'dRHjLZTHTJBuvgyhRfFLYaFiOjYAttzy');
    echo 'DB connected OK' . PHP_EOL;
    \$tables = \$pdo->query('SHOW TABLES')->fetchAll();
    echo 'Tables: ' . count(\$tables) . PHP_EOL;
    foreach(\$tables as \$t) echo '  - ' . \$t[0] . PHP_EOL;
} catch(Exception \$e) {
    echo 'DB FAIL: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

# Run migrations verbosely
echo "=== Running migrations ==="
php artisan migrate --force --no-interaction -v 2>&1

# Seed required data
echo "=== Seeding ==="
php artisan db:seed --force --no-interaction 2>&1 || echo "Seed skipped"

php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

echo "=== Starting server on port ${PORT:-8000} ==="
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
