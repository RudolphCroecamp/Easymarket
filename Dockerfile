FROM php:8.3-apache

# Install system dependencies (IMPORTANT: pkg-config helps GD detect WebP correctly)
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    pkg-config \
    libjpeg62-turbo-dev \
    libpng-dev \
    libfreetype6-dev \
    libwebp-dev \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure and install GD with JPEG + PNG + WebP support
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
&& docker-php-ext-install -j$(nproc) gd

# Install other PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN echo "memory_limit=256M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Change Apache to Cloud Run port (8080)
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first (better caching)
COPY composer.json composer.lock ./

RUN composer install --no-interaction --prefer-dist || true

# Copy application code
COPY . .

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080