FROM serversideup/php:8.3-fpm-nginx

ENV PHP_OPCACHE_ENABLE=1

USER root

COPY --chown=www-data:www-data . /var/www/html
COPY docker/entrypoint.d/ /etc/entrypoint.d/
RUN chmod +x /etc/entrypoint.d/*.sh

USER www-data
WORKDIR /var/www/html

RUN composer install --no-interaction --optimize-autoloader --no-dev
