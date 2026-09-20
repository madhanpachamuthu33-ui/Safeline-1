FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2dismod access_compat

RUN printf '%s\n' \
    '<Directory /var/www/html>' \
    '    Options Indexes FollowSymLinks' \
    '    AllowOverride None' \
    '    Require all granted' \
    '</Directory>' \
    > /etc/apache2/conf-available/safeline.conf

RUN a2enconf safeline

COPY . /var/www/html/

RUN rm -f /var/www/html/.htaccess

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
