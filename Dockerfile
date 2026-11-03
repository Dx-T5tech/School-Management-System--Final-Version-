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

EXPOSE 10000

CMD ["sh", "-c", "port=\"${PORT:-10000}\"; case \"$port\" in ''|*[!0-9]*) echo 'PORT must be numeric.' >&2; exit 1 ;; esac; sed -ri \"s/^Listen [0-9]+$/Listen ${port}/\" /etc/apache2/ports.conf; sed -ri \"s/<VirtualHost \\*:[0-9]+>/<VirtualHost *:${port}>/\" /etc/apache2/sites-available/000-default.conf; exec apache2-foreground"]
