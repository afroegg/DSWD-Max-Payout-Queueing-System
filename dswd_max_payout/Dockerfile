FROM php:8.2-apache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip git libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli zip mbstring gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/
WORKDIR /var/www/html

RUN composer require phpoffice/phpspreadsheet:^2.2 --no-dev --prefer-dist --no-interaction --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html
RUN a2enmod rewrite

EXPOSE 80
