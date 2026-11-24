FROM php:8.4-apache

# Extensoes necessarias (PDO/MySQL), reescrita de URL e cabecalhos de seguranca
RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite headers reqtimeout

WORKDIR /var/www/html

# Configuracoes de seguranca do PHP (display_errors off, expose_php off...)
COPY php-security.ini /usr/local/etc/php/conf.d/zz-security.ini

# VirtualHost da aplicacao (DocumentRoot em /public)
# Copiado antes do codigo para aproveitar o cache de camadas
COPY apache.conf /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/

EXPOSE 80
