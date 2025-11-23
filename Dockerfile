# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install required system packages and PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql zip gd intl opcache \
    && apt-get clean

# Set working directory
WORKDIR /var/www/html

# Copy the nested project folder into the container
COPY CAPSTONE_LMS_EHS/ /var/www/html/CAPSTONE_LMS_EHS/

# Create a root index.php to redirect to your nested folder
RUN echo "<?php header('Location: /CAPSTONE_LMS_EHS/index.php'); exit; ?>" > /var/www/html/index.php

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (Apache)
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
