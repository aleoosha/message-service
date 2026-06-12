FROM php:8.3-fpm-alpine

# Установка системных зависимостей, компилятора и заголовков ядра
# ИСПРАВЛЕНО: Добавлены pcre2-dev и bash, необходимые для сборки расширения rdkafka
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libxml2-dev \
    zip unzip git oniguruma-dev linux-headers \
    librdkafka-dev pcre2-dev bash \
    $PHPIZE_DEPS 

# Раздельная последовательная компиляция пакетов через PECL
RUN pecl install redis \
    && pecl install rdkafka \
    && docker-php-ext-enable redis rdkafka

# Накатываем расширения СУБД и воркеров Кафки
RUN docker-php-ext-install pdo_pgsql pgsql bcmath gd mbstring pcntl

# Удаляем инструменты сборки для облегчения итогового образа
RUN apk del $PHPIZE_DEPS

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Копируем конфигурацию пакетов для кэширования слоя Docker
COPY composer.json composer.lock ./

# Выкачиваем вендор прямо в образ на максимальной скорости
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs

# Копируем остальной исходный код проекта
COPY . .

# Генерируем финальный оптимизированный автозагрузчик классов
RUN composer dump-autoload --optimize --ignore-platform-reqs

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/storage \
    && chmod -R 777 /var/www/html/bootstrap/cache

EXPOSE 8000
CMD ["php-fpm"]
