FROM php:8.2-apache

# Instalar extensiones PHP necesarias
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Habilitar mod_rewrite y deshabilitar MPM extra
RUN a2enmod rewrite && a2dismod mpm_event && a2enmod mpm_prefork

# Copiar código al directorio de Apache
COPY . /var/www/html/

# Apuntar Apache a /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Permisos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
