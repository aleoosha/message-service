#!/bin/sh
set -e

# Если папка vendor отсутствует, устанавливаем зависимости
if [ ! -d "vendor" ]; then
    echo "Папка vendor не найдена. Запуск composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    
    # Сразу ставим пакет для работы с Кафкой внутри контейнера
    echo "Установка mateusjunges/laravel-kafka..."
    composer require mateusjunges/laravel-kafka --no-interaction
fi

# Генерируем APP_KEY, если он не задан или пустой
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "Генерация APP_KEY..."
    php artisan key:generate --no-interaction
fi

# Передаем управление основному процессу контейнера (например, php-fpm или php artisan)
exec "$@"
