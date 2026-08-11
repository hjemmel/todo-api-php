FROM php:8.5-cli-alpine

# Xdebug needs the build toolchain; git/unzip let Composer work with VCS + dist packages.
RUN apk add --no-cache git unzip linux-headers $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS

# Coverage only: no step debugging, so the container never stalls waiting for an IDE.
RUN printf 'xdebug.mode=coverage\n' > /usr/local/etc/php/conf.d/xdebug-mode.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

WORKDIR /var/www
