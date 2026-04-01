#!/bin/bash
# Railway asigna el puerto en $PORT — Apache debe escuchar ahí
PORT=${PORT:-80}

# Reemplazar el puerto en la configuración de Apache
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf

apache2-foreground
