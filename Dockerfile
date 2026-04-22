FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl

# Enable Apache rewrite
RUN a2enmod rewrite

# 🔥 FIX: MySQL support
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Change Apache to Cloud Run port
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist || true

COPY . .

RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080