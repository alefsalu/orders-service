# Multi-stage Dockerfile for orders-service with FrankenPHP and PHP 8.3
FROM dunglas/frankenphp:1-php8.3-alpine AS base

# Install system dependencies and PHP extensions required for orders-service
RUN apk add --no-cache \
    curl \
    git \
    bash \
    rabbitmq-c-dev \
    libzip-dev \
    openssl \
    openssl-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        opcache \
    && pecl install amqp mongodb \
    && docker-php-ext-enable amqp mongodb \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/* /var/cache/apk/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Non-root user setup
RUN addgroup -g 1000 appgroup && \
    adduser -u 1000 -G appgroup -s /bin/bash -D appuser && \
    mkdir -p /app/var /app/keys && \
    chown -R appuser:appgroup /app

ENV PORT=8081
ENV SERVER_NAME=":8081"
EXPOSE 8081

HEALTHCHECK --interval=10s --timeout=3s --retries=3 \
    CMD curl -f http://localhost:8081/health/live || exit 1

FROM base AS dev
USER appuser
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

FROM base AS prod
COPY --chown=appuser:appgroup . /app
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
USER appuser
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
