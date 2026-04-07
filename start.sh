#!/bin/bash
echo "=== EzzeSend Starting ==="
echo "PORT: $PORT | APP_ENV: $APP_ENV | DB_HOST: $DB_HOST"

# Write Railway environment variables into .env file
# This is needed because Laravel prioritizes .env over OS env vars
cat > .env << ENVFILE
APP_NAME=${APP_NAME:-EzzeSend}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}

LOG_CHANNEL=stack
LOG_LEVEL=${LOG_LEVEL:-error}

DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
BROADCAST_DRIVER=log
FILESYSTEM_DISK=local

PUSHER_APP_ID=${PUSHER_APP_ID}
PUSHER_APP_KEY=${PUSHER_APP_KEY}
PUSHER_APP_SECRET=${PUSHER_APP_SECRET}
PUSHER_APP_CLUSTER=${PUSHER_APP_CLUSTER:-mt1}

SHOPIFY_CLIENT_ID=${SHOPIFY_CLIENT_ID}
SHOPIFY_REDIRECT_URI=${SHOPIFY_REDIRECT_URI}
ENVFILE

echo "DB_HOST in .env: $(grep DB_HOST .env)"

# Clear config cache and rebuild from new .env
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

# Run migrations
echo "Running migrations..."
php artisan migrate --force --no-interaction 2>&1 || echo "Migration warning — continuing"

# Cache
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

echo "Starting server on port ${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
