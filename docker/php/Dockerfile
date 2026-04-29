FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    bcmath \
    gd \
    zip \
    exif \
    pcntl \
    opcache

# OPcache config — cache compiled PHP bytecode
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/opcache.ini

# PHP-FPM pool tuning — more workers
RUN echo "[www]" > /usr/local/etc/php-fpm.d/zz-tuning.conf \
    && echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/zz-tuning.conf \
    && echo "pm.max_children = 20" >> /usr/local/etc/php-fpm.d/zz-tuning.conf \
    && echo "pm.start_servers = 5" >> /usr/local/etc/php-fpm.d/zz-tuning.conf \
    && echo "pm.min_spare_servers = 3" >> /usr/local/etc/php-fpm.d/zz-tuning.conf \
    && echo "pm.max_spare_servers = 10" >> /usr/local/etc/php-fpm.d/zz-tuning.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
