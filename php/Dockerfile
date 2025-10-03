FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# 1. Instala dependências
RUN apk add --no-cache \
        postgresql-libs libzip libpng libjpeg-turbo \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
        postgresql-dev libzip-dev libpng-dev libjpeg-turbo-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip gd \
    && apk del .build-deps

# 2. Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Copia o composer.json (da raiz do projeto) e instala
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader

# 4. Copia as configurações do PHP
COPY php/config/uploads.ini /usr/local/etc/php/conf.d/uploads.ini