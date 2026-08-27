FROM php:8.5-fpm-alpine

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN apk add --no-cache icu-dev git sudo $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo_mysql \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del $PHPIZE_DEPS \
    && git config --system --add safe.directory /srv \
    && addgroup -g ${GROUP_ID} app \
    && adduser -D -u ${USER_ID} -G app -h /home/app app \
    && echo 'app ALL=(ALL) NOPASSWD:ALL' > /etc/sudoers.d/app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN { \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'date.timezone=UTC'; \
        echo 'memory_limit=1G'; \
    } > /usr/local/etc/php/conf.d/app.ini

WORKDIR /srv
