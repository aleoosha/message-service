FROM php:8.3-fpm-alpine

# Устанавливаем системные зависимости и готовое расширение rdkafka из репозиториев Alpine
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    linux-headers \
    librdkafka-dev \
    php83-pecl-rdkafka

# Переносим расширение туда, где его ищет официальный образ PHP, и активируем его
RUN cp /usr/lib/php83/modules/rdkafka.so $(php-config --extension-dir)/rdkafka.so \
    && docker-php-ext-enable rdkafka

# Для сборки Redis используем временные инструменты сборки
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Установка встроенных расширений PHP
RUN docker-php-ext-install pdo_pgsql pgsql bcmath gd mbstring pcntl

# Копируем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Копируем исходный код проекта
COPY . .

# Выставляем права на папки для Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/storage \
    && chmod -R 777 /var/www/html/bootstrap/cache

# Копируем и настраиваем скрипт инициализации
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
