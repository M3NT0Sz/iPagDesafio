FROM php:8.2-apache

# Instala extensões necessárias

RUN apt-get update && apt-get install -y default-mysql-client \
	&& docker-php-ext-install pdo pdo_mysql

# Ativa o mod_rewrite do Apache
RUN a2enmod rewrite

# Copia configuração customizada do Apache
COPY apache-config.conf /etc/apache2/conf-enabled/apache-config.conf

# Define o diretório de trabalho
WORKDIR /var/www/html
