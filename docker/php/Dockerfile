FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libfcgi-bin \
        libicu-dev \
        libpng-dev \
        libzip-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
        opcache \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN printf '%s\n' \
        '[www]' \
        'ping.path = /ping' \
        > /usr/local/etc/php-fpm.d/zz-ping.conf

COPY entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r$//' /entrypoint.sh && chmod +x /entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
