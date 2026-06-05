FROM php:8.2-apache

RUN apt-get update && apt-get install -y libonig-dev && rm -rf /var/lib/apt/lists/* && docker-php-ext-install pdo pdo_mysql mbstring \
 && a2enmod rewrite \
 && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY src/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s CMD curl -f "http://localhost/api.php?path=health" || exit 1
