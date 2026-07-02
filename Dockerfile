FROM serversideup/php:8.4-fpm-nginx

ENV PHP_OPCACHE_ENABLE=1
ENV AUTORUN_ENABLED=true
ENV AUTORUN_LARAVEL_MIGRATION=false

USER root

COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/
COPY --chown=www-data:www-data . /var/www/html

RUN docker-php-serversideup-s6-init

USER www-data
WORKDIR /var/www/html

RUN composer install --no-interaction --optimize-autoloader --no-dev
