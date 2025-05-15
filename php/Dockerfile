FROM php:8.2-fpm

# Inštalácia MySQL klienta a PDO pre PHP
RUN apt-get update && apt-get install -y default-mysql-client \
    && docker-php-ext-install pdo_mysql

# Inštalácia Pythonu, pipu a knižníc pypdf + reportlab
RUN apt-get update && apt-get install -y python3 python3-pip
RUN pip3 install --break-system-packages pypdf reportlab
# Inštalácia Composeru
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# Práca v projektovom adresári
WORKDIR /var/www/html

# Skript na čakanie na MySQL
COPY wait-for-mysql.sh /usr/local/bin/wait-for-mysql
RUN chmod +x /usr/local/bin/wait-for-mysql

COPY ./php.ini /usr/local/etc/php/conf.d/uploads.ini

CMD ["php-fpm"]