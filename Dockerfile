FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libjpeg62-turbo-dev \
    libpng-dev \
    libfreetype6-dev

# Enable Apache rewrite
RUN a2enmod rewrite

# Install GD + MySQL extensions
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd mysqli pdo pdo_mysql

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