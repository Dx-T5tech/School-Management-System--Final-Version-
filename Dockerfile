FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . .

RUN mkdir -p /var/www/html/storage/uploads \
    && chown -R www-data:www-data /var/www/html/storage

EXPOSE 80
