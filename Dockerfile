FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

RUN a2enmod rewrite

WORKDIR /var/www/html

COPY src/ /var/www/html/

COPY db.sql /docker-entrypoint-initdb.d/

RUN chown -R www-data:www-data /var/www/html

RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    DirectoryIndex login.php index.php\n\
</Directory>' > /etc/apache2/conf-available/default-directory-config.conf

RUN a2enconf default-directory-config

EXPOSE 3050

CMD ["apache2-foreground"]