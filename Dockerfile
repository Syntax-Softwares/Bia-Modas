FROM php:8.4-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod rewrite

COPY . /var/www/html/

COPY apache.conf /etc/apache2/sites-available/000-default.conf