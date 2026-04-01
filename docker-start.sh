#!/bin/bash
PORT=${PORT:-80}

sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf

# En Ubuntu el comando es apache2ctl, no apache2-foreground
source /etc/apache2/envvars
exec apache2 -D FOREGROUND
