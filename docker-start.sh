#!/bin/bash
PORT=${PORT:-80}

sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf

# Agregar acceso a controllers/, config/, libs/ fuera de public/
cat >> /etc/apache2/sites-available/000-default.conf << 'EOF'

Alias /controllers /var/www/html/controllers
<Directory /var/www/html/controllers>
    Options -Indexes
    AllowOverride All
    Require all granted
</Directory>

Alias /config /var/www/html/config
<Directory /var/www/html/config>
    Options -Indexes
    AllowOverride All
    Require all granted
</Directory>

Alias /libs /var/www/html/libs
<Directory /var/www/html/libs>
    Options -Indexes
    AllowOverride All
    Require all granted
</Directory>
EOF

source /etc/apache2/envvars
exec apache2 -D FOREGROUND
