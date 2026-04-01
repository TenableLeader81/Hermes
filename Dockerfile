FROM php:8.2-apache

# Instalar extensiones PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Desactivar todos los MPM y dejar solo prefork
RUN a2dismod mpm_event mpm_worker mpm_prefork 2>/dev/null || true && \
    a2enmod mpm_prefork && \
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
