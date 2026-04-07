#!/bin/bash
set -e

echo "=== EzzeSend Startup ==="

# Clear cached config so Railway env vars take effect
php artisan config:clear
php artisan cache:clear

# Run migrations (with timeout protection)
echo "Running migrations..."
php artisan migrate --force --no-interaction || echo "Migration warning (may already be up to date)"

# Cache config with real Railway values
php artisan config:cache
php artisan route:cache

echo "Starting server on port $PORT..."
php artisan serve --host=0.0.0.0 --port=$PORT
