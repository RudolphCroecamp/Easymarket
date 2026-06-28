FROM php:8.3-apache

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


# Enable Apache rewrite
RUN a2enmod rewrite


# Configure GD WITH WEBP
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) gd


# Other PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql


# Cloud Run Apache port
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


WORKDIR /var/www/html


COPY composer.json composer.lock ./

RUN composer install --no-interaction --prefer-dist || true


COPY . .


RUN chown -R www-data:www-data /var/www/html


EXPOSE 8080