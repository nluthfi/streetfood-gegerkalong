FROM php:8.5-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod headers rewrite

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/streetfood.ini
COPY . /var/www/html/

RUN mkdir -p /var/www/html/public/assets/images/pict \
        /var/www/html/public/assets/images/makanan \
        /var/www/html/public/assets/images/logo \
    && chown -R www-data:www-data /var/www/html/public/assets/images

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD php -r "exit(@file_get_contents('http://127.0.0.1/health.php') === false ? 1 : 0);"
