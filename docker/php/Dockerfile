FROM php:8.5.1-fpm

# Installation de l'extension pecl pcov
RUN pecl install pcov \
    && docker-php-ext-enable pcov

EXPOSE 9000
