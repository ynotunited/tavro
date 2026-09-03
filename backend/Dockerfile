# Tavro backend runtime image.
#
# A single image powers every process type on Railway, switched by the
# PROCESS_TYPE env var (web | reverb | worker). Set the Railway service's
# "Root Directory" to `backend` and build this Dockerfile.

FROM php:8.2-fpm-alpine AS base

# System + PHP dependencies required by Laravel, Postgres, Reverb (pcntl/sockets)
# and Redis. pcntl + sockets are mandatory for Laravel Reverb to run as a
# long-lived WebSocket server.
RUN apk add --no-cache \
        libpq \
        icu-dev \
        oniguruma-dev \
        libzip-dev \
        zip \
        unzip \
        git \
        curl \
        ca-certificates \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        pcntl \
        bcmath \
        intl \
        zip \
        sockets \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www/html

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install dependencies first (layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress

# Copy the application
COPY . .

RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi || true

# Laravel writable dirs
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/tavro-entrypoint
RUN chmod +x /usr/local/bin/tavro-entrypoint

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/tavro-entrypoint"]
