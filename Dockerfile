# syntax=docker/dockerfile:1

FROM composer:2 AS dependencies

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --classmap-authoritative

FROM php:8.2-apache AS production

ENV APACHE_DOCUMENT_ROOT=/var/www/html \
    APP_ENV=production \
    APP_DEBUG=false

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev libonig-dev \
    && docker-php-ext-install -j"$(nproc)" curl mbstring pdo_mysql \
    && a2enmod rewrite headers expires deflate \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/server-name.conf \
    && a2enconf server-name \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=dependencies /app/vendor ./vendor
COPY --from=dependencies /usr/bin/composer /usr/bin/composer
COPY . .

RUN composer dump-autoload \
    --no-dev \
    --optimize \
    --classmap-authoritative \
    --no-interaction \
    && mkdir -p storage/logs storage/uploads \
    && chown -R www-data:www-data storage \
    && find storage -type d -exec chmod 755 {} \; \
    && find storage -type f -exec chmod 644 {} \;

COPY docker/php-production.ini /usr/local/etc/php/conf.d/zz-production.ini

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD php -r '$c=@file_get_contents("http://127.0.0.1/", false, stream_context_create(["http"=>["timeout"=>3]])); exit($c === false ? 1 : 0);'

EXPOSE 80

CMD ["apache2-foreground"]
