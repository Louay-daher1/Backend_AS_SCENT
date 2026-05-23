# syntax=docker/dockerfile:1

# -----------------------------------------------------------------------------
# Base: PHP 8.3 + Laravel-required extensions
# -----------------------------------------------------------------------------
FROM php:8.3-cli-bookworm AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
        libcurl4-openssl-dev \
        libxml2-dev \
        libonig-dev \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        zip \
        pdo_mysql \
        mbstring \
        xml \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    COMPOSER_CACHE_DIR=/tmp/composer-cache

# Verify required extensions (fail build early if any are missing)
RUN php -r " \
    \$required = ['intl', 'zip', 'pdo_mysql', 'mbstring', 'curl', 'xml', 'tokenizer', 'fileinfo']; \
    foreach (\$required as \$ext) { \
        if (! extension_loaded(\$ext)) { \
            fwrite(STDERR, \"Missing PHP extension: {\$ext}\" . PHP_EOL); \
            exit(1); \
        } \
    } \
    if (! defined('OPENSSL_VERSION_TEXT')) { \
        fwrite(STDERR, 'OpenSSL is not available.' . PHP_EOL); \
        exit(1); \
    } \
    echo 'All required PHP extensions are available.' . PHP_EOL; \
"

WORKDIR /app

# -----------------------------------------------------------------------------
# Vendor: cached Composer layer (rebuilds only when lock files change)
# -----------------------------------------------------------------------------
FROM base AS vendor

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts

# -----------------------------------------------------------------------------
# Build: copy application and finish Composer autoload / package discovery
# -----------------------------------------------------------------------------
FROM vendor AS build

COPY . .

RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

# -----------------------------------------------------------------------------
# Production runtime
# -----------------------------------------------------------------------------
FROM base AS production

WORKDIR /app

COPY --from=build /app /app

RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD php -r "exit(@file_get_contents('http://127.0.0.1:' . (getenv('PORT') ?: '8080') . '/up') === false);"

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
