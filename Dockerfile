FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl

# Enable Apache rewrite
RUN a2enmod rewrite

# 🔥 IMPORTANT: Change Apache to listen on 8080 (Cloud Run requirement)
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (better caching)
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist || true

# Copy full project (includes .env, api, rsc, index.html)
COPY . .

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

# Cloud Run listens on 8080
EXPOSE 8080