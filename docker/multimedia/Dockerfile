FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    linux-headers \
    ffmpeg \
    libqrencode \
    sqlite \
    sqlite-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    imagemagick \
    imagemagick-dev \
    git \
    unzip \
    curl \
    postgresql-dev

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    pdo \
    pdo_sqlite \
    pdo_mysql \
    pdo_pgsql \
    intl \
    mbstring \
    zip \
    bcmath \
    exif

# Install Imagick PECL extension
RUN pecl install imagick \
    && docker-php-ext-enable imagick

# Increase PHP memory limit and upload size
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize=100M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/uploads.ini

# Set working directory
WORKDIR /var/www/html

# Default command
CMD ["php-fpm"]
