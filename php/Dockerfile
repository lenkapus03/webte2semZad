FROM php:8.2-fpm  
RUN apt-get update && apt-get install -y default-mysql-client \
    && docker-php-ext-install pdo_mysql

RUN apt-get update && apt-get install -y python3 python3-pip
RUN pip3 install pypdf --break-system-packages


COPY wait-for-mysql.sh /usr/local/bin/wait-for-mysql
RUN chmod +x /usr/local/bin/wait-for-mysql

COPY ./php.ini /usr/local/etc/php/conf.d/uploads.ini


CMD ["php-fpm"]
