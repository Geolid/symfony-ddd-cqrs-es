FROM php:8.5-fpm-alpine

RUN apk add --no-cache icu-dev $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo_mysql opcache \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN { \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'date.timezone=UTC'; \
    } > /usr/local/etc/php/conf.d/app.ini

WORKDIR /srv
