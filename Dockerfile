FROM php:5.6-apache
RUN docker-php-ext-install pdo pdo_mysql

ADD src /var/www/html/
ADD scripts /opt/
ADD data /opt/

CMD ["/opt/startup.sh"]