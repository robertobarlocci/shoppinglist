FROM php:8.3-fpm-alpine

# Build arguments for flexibility
ARG PHP_VERSION=8.3
ARG NODE_VERSION=20

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    zip \
    unzip \
    postgresql-dev \
    nodejs \
    npm \
    supervisor \
    fcgi

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache

# Install Redis extension
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev $PHPIZE_DEPS

# Install php-fpm-healthcheck
RUN wget -O /usr/local/bin/php-fpm-healthcheck \
    https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN addgroup -g 1000 www && \
    adduser -D -u 1000 -G www www

# Copy existing application directory permissions
COPY --chown=www:www . /var/www

# Copy PHP-FPM configuration for health checks
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
