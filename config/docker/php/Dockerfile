FROM php:8.2-fpm-alpine

USER root

ENV LANG pl_PL.UTF-8
ENV LC_ALL pl_PL.UTF-8

# Install build tools and dependencies, configure PHP extensions, install composer, and set timezone.
RUN apk add --no-cache git libzip-dev icu-dev gcc make autoconf libc-dev bash tzdata linux-headers wget \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip intl \
    && docker-php-ext-enable intl \
    && cp /usr/share/zoneinfo/Europe/Warsaw /etc/localtime \
    && echo "Europe/Warsaw" > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "Europe/Warsaw"\n' > /usr/local/etc/php/conf.d/tzone.ini \
    && wget -q -O /usr/bin/composer https://getcomposer.org/download/latest-stable/composer.phar \
    && chmod +x /usr/bin/composer \
    && apk del --no-cache tzdata

RUN addgroup -g 1000 comfino && adduser -u 1000 -G comfino -s /bin/sh -D comfino

USER comfino

WORKDIR /var/www/backend-integration-demo.test
