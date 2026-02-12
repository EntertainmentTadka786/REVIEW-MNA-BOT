# Use official PHP image with Apache
FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install gd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install zip \
    && docker-php-ext-install session

# Enable Apache mod_rewrite
RUN a2enmod rewrite
RUN a2enmod headers

# Copy application files
COPY . .

# Set proper permissions
RUN chmod -R 755 /var/www/html
RUN chown -R www-data:www-data /var/www/html
RUN chmod 666 users.json movies.csv error.log requests.json bot_stats.json
RUN mkdir -p backups cache && chmod 777 backups cache

# Create empty files if they don't exist
RUN touch error.log movies.csv users.json bot_stats.json requests.json && \
    chmod 666 error.log movies.csv users.json bot_stats.json requests.json

# Set PHP configuration
RUN echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "session.save_path = /tmp" >> /usr/local/etc/php/conf.d/session.ini && \
    echo "session.gc_maxlifetime = 1440" >> /usr/local/etc/php/conf.d/session.ini

# Set ServerName to avoid Apache warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]