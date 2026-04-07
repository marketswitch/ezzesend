#!/bin/bash

echo "=== EzzeSend Starting ==="
echo "PORT: $PORT"
echo "APP_ENV: $APP_ENV"

# Step 1: Clear any cached config from build time
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

# Step 2: Run migrations (non-fatal if DB not ready)
echo "Running migrations..."
php artisan migrate --force --no-interaction 2>&1 || echo "Migration skipped or failed — continuing"

# Step 3: Cache config with real runtime values
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

# Step 4: Start the server
echo "Starting server on 0.0.0.0:$PORT"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
