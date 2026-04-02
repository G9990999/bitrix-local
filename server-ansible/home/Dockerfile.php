FROM php:8.2-fpm-alpine

# Установка системных зависимостей (добавлен postgresql-dev в apk add)
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pgsql \
        gd \
        zip \
        intl \
        opcache \
        mbstring

# Рекомендуемые настройки для Битрикс
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'memory_limit=512M'; \
    echo 'upload_max_filesize=100M'; \
    echo 'post_max_size=100M'; \
    echo 'date.timezone=Europe/Moscow'; \
    } > /usr/local/etc/php/conf.d/bitrix.ini

# Создаем исполняемый файл bx
RUN echo '#!/bin/sh' > /usr/local/bin/bx \
    && echo 'php /var/www/html/local/modules/rolemodel.cli/cli.php "$@"' >> /usr/local/bin/bx \
    && chmod +x /usr/local/bin/bx