FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Instalar Apache y PHP limpio
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-mysql \
    php8.1-curl \
    php8.1-mbstring \
    libapache2-mod-php8.1 \
    && apt-get clean

# Habilitar rewrite
RUN a2enmod rewrite

# Configurar DocumentRoot a /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf && \
    sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/apache2.conf && \
    sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copiar código
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

COPY docker-start.sh /docker-start.sh
RUN chmod +x /docker-start.sh

CMD ["/docker-start.sh"]
