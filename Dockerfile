FROM php:8.5-apache

COPY . /var/www/html/
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod headers rewrite

EXPOSE 80
