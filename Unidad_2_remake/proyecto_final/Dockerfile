# E-Waste 360° — imagen para Render (o cualquier hosting con Docker).
# PHP 8.3 + Apache, con drivers para PostgreSQL (Neon) y MySQL.
FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql opcache \
    && rm -rf /var/lib/apt/lists/*

# Configuracion de PHP para produccion (sin errores en pantalla, sin firma de version)
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/ewaste.ini"

# Apache: solo public/ es accesible y se habilita mod_rewrite para el Front Controller
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN a2enmod rewrite headers \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
COPY docker/apache.conf /etc/apache2/conf-available/ewaste.conf
RUN a2enconf ewaste

WORKDIR /var/www/html
COPY . .
RUN sed -i 's/\r$//' docker/entrypoint.sh \
    && chmod +x docker/entrypoint.sh \
    && rm -f .env \
    && chown -R www-data:www-data /var/www/html

# Render asigna el puerto en la variable PORT; el entrypoint ajusta Apache
EXPOSE 10000
ENTRYPOINT ["docker/entrypoint.sh"]
