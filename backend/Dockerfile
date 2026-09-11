FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        icu-dev \
        libzip-dev \
        libpng-dev \
        oniguruma-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        nodejs \
        npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        intl \
        zip \
        gd \
        bcmath \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Installs dev dependencies too (Pint, PHPUnit, Faker), and Node.js above,
# since this image doubles as the VS Code dev container for the whole repo.
# A production deployment should build its own image with
# `composer install --no-dev --optimize-autoloader` and no Node.js.
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

EXPOSE 9000
CMD ["php-fpm"]
