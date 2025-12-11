FROM php:8.2-fpm-alpine

# Install git and system deps
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    linux-headers \
    git \
    sqlite \
    sqlite-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    imagemagick \
    imagemagick-dev \
    libzip-dev

# Install extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_sqlite intl zip bcmath exif \
    && pecl install imagick \
    && docker-php-ext-enable imagick

# Copy entrypoint
COPY entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
