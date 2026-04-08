#!/bin/bash
set -e

echo "=== EzzeSend Starting ==="

# ---------------------------------------------------------------------------
# Populate .env with Railway MySQL environment variables at runtime.
# Railway injects these from the linked MySQL service; they are not available
# at image-build time, so we must write them into .env here before Laravel
# bootstraps its database connection.
# ---------------------------------------------------------------------------
echo "Updating .env with Railway database credentials..."

sed -i "s|^DB_HOST=.*|DB_HOST=${MYSQL_HOST}|"         .env
sed -i "s|^DB_PORT=.*|DB_PORT=${MYSQL_PORT}|"         .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${MYSQL_DATABASE}|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${MYSQL_USER}|"  .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${MYSQL_PASSWORD}|" .env

echo "DB_HOST     -> ${MYSQL_HOST}"
echo "DB_PORT     -> ${MYSQL_PORT}"
echo "DB_DATABASE -> ${MYSQL_DATABASE}"
echo "DB_USERNAME -> ${MYSQL_USER}"

# ---------------------------------------------------------------------------
# Wait for the database to be reachable before running migrations.
# ---------------------------------------------------------------------------
echo "Testing database connection..."
php -r "
\$host     = getenv('MYSQL_HOST');
\$port     = getenv('MYSQL_PORT') ?: 3306;
\$dbname   = getenv('MYSQL_DATABASE');
\$user     = getenv('MYSQL_USER');
\$password = getenv('MYSQL_PASSWORD');

try {
    \$dsn = \"mysql:host={\$host};port={\$port};dbname={\$dbname};connect_timeout=10\";
    \$pdo = new PDO(\$dsn, \$user, \$password);
    echo 'DB connected OK' . PHP_EOL;
} catch (Exception \$e) {
    echo 'DB FAIL: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

php artisan migrate --force --no-interaction 2>&1
php artisan db:seed --force --no-interaction 2>&1 || true
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

echo "Starting on port ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
