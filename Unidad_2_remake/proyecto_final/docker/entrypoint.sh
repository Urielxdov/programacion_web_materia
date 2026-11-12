#!/bin/sh
# Arranque del contenedor: ajusta el puerto de Apache, prepara la base de datos
# (solo la primera vez: --si-vacia) y lanza Apache en primer plano.
set -e

PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ "${AUTO_INSTALL:-true}" = "true" ]; then
    php /var/www/html/database/instalar.php --si-vacia \
        || echo "AVISO: no se pudo preparar la base de datos. Revisa DATABASE_URL en Render."
fi

exec apache2-foreground
