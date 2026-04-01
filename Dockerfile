FROM php:8.2-apache

# Instalar extensiones PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Limpiar todos los MPM habilitados y dejar solo prefork
RUN find /etc/apache2/mods-enabled -name "mpm_*" -delete && \
    ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf && \
    ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load && \
    a2enmod rewrite

# Apuntar DocumentRoot a /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf && \
    sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copiar código
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
