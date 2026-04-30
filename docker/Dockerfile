# Dockerfile
FROM dunglas/frankenphp:php8.4-alpine

# System deps
RUN apk add --no-cache \
    linux-headers \
    icu-dev \
    libzip-dev \
    zlib-dev

# PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    sockets \
    pcntl \
    exif \
    bcmath \
    intl \
    zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set PHP memory limit to 1024M
RUN echo "memory_limit = 1024M" > /usr/local/etc/php/conf.d/memory-limit.ini