#!/bin/sh

set -e

echo "=== ЗАПУСК ОДНОРАЗОВОЙ ИНИЦИАЛИЗАЦИИ СРЕДЫ ==="

# 1. Автоматическая генерация файла конфигурации
if [ ! -f .env ]; then
    echo "Файл .env отсутствует. Создание из шаблона .env.example..."
    cp .env.example .env
    php artisan key:generate --no-interaction
fi

# 2. Ожидание и накат миграций PostgreSQL
echo "Запуск миграций базы данных PostgreSQL..."
php artisan migrate --force --no-interaction

echo "=== ИНИЦИАЛИЗАЦИЯ УСПЕШНО ЗАВЕРШЕНА ==="
