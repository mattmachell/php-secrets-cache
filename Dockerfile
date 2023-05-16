FROM php:8-alpine

RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS \
        && pecl install apcu \
        && docker-php-ext-enable apcu \
        && pecl install pcov  \
        && docker-php-ext-enable pcov \
        && pecl clear-cache \
        && apk del .build-dependencies\
        && echo "apc.enabled=1" >> /usr/local/etc/php/conf.d/apcu.ini \
        && echo "apc.enable_cli=1" >> /usr/local/etc/php/conf.d/apcu.ini \
        && echo "pcov.enabled = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini \
        && echo "pcov.directory = /var/www" >> /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini 

