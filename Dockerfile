FROM php:7.4-apache

# Install mysqli and other useful extensions
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy your code (optional if you're using volumes)
COPY ./web /var/www/html/

# Enable Apache mod_rewrite if needed
RUN a2enmod rewrite
