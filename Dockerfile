FROM php:8.2-apache

# Instalar extensiones PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Habilitar rewrite
RUN a2enmod rewrite

# Apuntar DocumentRoot a /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf && \
    sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copiar código
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Script que usa $PORT de Railway
COPY docker-start.sh /docker-start.sh
RUN chmod +x /docker-start.sh

CMD ["/docker-start.sh"]
