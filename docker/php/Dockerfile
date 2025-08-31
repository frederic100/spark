FROM php:8.4.12-fpm

# Installation de l'extension pecl pcov
RUN pecl install pcov \
    && docker-php-ext-enable pcov

EXPOSE 9000
