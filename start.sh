#!/bin/bash
echo "=== EzzeSend Starting ==="
echo "ENV: $APP_ENV | PORT: $PORT"

# Clear build-time cached config, load Railway env vars
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

# Run migrations
echo "Running migrations..."
php artisan migrate --force --no-interaction 2>&1 || echo "Migration warning — continuing"

# Cache with real values
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

echo "Starting on port ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
