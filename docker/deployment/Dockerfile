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
    libzip-dev \
    postgresql-dev \
    ffmpeg \
    libqrencode \
    oniguruma-dev \
    unzip \
    curl

# Install extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_sqlite pdo_mysql pdo_pgsql intl zip bcmath exif mbstring \
    && pecl install imagick \
    && docker-php-ext-enable imagick

# Increase PHP memory limit and upload size
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize=100M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/uploads.ini

# Copy entrypoint
COPY entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
