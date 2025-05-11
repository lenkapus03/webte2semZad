FROM php:8.2-fpm  
RUN apt-get update && apt-get install -y default-mysql-client \
    && docker-php-ext-install pdo_mysql

COPY wait-for-mysql.sh /usr/local/bin/wait-for-mysql
RUN chmod +x /usr/local/bin/wait-for-mysql


CMD ["php-fpm"]
