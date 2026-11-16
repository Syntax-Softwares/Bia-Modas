FROM php:8.4-apache

# Extensoes necessarias (PDO/MySQL) e reescrita de URL
RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite

WORKDIR /var/www/html

# VirtualHost da aplicacao (DocumentRoot em /public)
# Copiado antes do codigo para aproveitar o cache de camadas
COPY apache.conf /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/

EXPOSE 80
