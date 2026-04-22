FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl


RUN a2enmod rewrite


RUN docker-php-ext-install mysqli openssl


RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist || true

COPY . .

RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080