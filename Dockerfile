FROM php:8.3-cli

# Install system dependencies and PHP extensions required by Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libgmp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        xml \
        gmp \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Create required directories BEFORE copying application files so that
# composer's post-autoload-dump scripts (artisan package:discover) can
# find a writable bootstrap/cache at install time.
RUN mkdir -p bootstrap/cache \
    && mkdir -p storage/framework/cache/data \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs

# Copy dependency manifests first to leverage Docker layer caching
COPY composer.json composer.lock ./

# Copy the .env so artisan commands invoked by composer scripts can
# bootstrap the application (APP_KEY, etc.)
COPY .env ./

# Install PHP dependencies — post-autoload-dump runs `artisan package:discover`
# here, which requires bootstrap/cache to already exist (created above).
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --prefer-dist

# Copy the rest of the application source
COPY . .

# Ensure storage and cache directories are writable by the web process
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

CMD ["bash", "start.sh"]
