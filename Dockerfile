# syntax=docker/dockerfile:1
FROM php:8.4-cli-alpine

# System deps + PHP extensions Symfony/Doctrine need (SQLite + intl).
# $PHPIZE_DEPS (build toolchain) is added temporarily just to compile intl,
# then dropped so the runtime image stays small.
RUN apk add --no-cache icu-libs sqlite-libs git unzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev sqlite-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" intl pdo pdo_sqlite opcache \
    && apk del .build-deps

# Composer (copied from the official image).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Reasonable defaults for a dev container.
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=dev

EXPOSE 8000

# Built-in PHP server is enough for a demo; document root is public/.
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
