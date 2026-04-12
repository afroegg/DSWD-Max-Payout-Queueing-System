FROM php:8.2-apache

# Enable mysqli
RUN docker-php-ext-install mysqli

# Copy project
COPY . /var/www/html/

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80