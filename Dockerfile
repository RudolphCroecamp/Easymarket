FROM php:8.3-apache

# Enable Apache rewrite (useful later for clean URLs)
RUN a2enmod rewrite

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (better caching)
COPY composer.json composer.lock ./

# Install PHP dependencies (including phpdotenv)
RUN composer install --no-interaction --prefer-dist

# Copy the rest of your project (including .env)
COPY . .

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

# Expose Apache port
EXPOSE 8080