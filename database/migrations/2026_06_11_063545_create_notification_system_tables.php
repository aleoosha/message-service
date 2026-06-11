<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. БИЗНЕС-ТАБЛИЦА: Хранит состояние внутри Laravel (если нужно для локальной логики)
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique()->index()
                ->comment('Глобальный уникальный ID уведомления');

            $table->string('idempotency_key')->unique()
                ->comment('Ключ идемпотентности от вызывающего сервиса для предотвращения дублей');

            $table->unsignedBigInteger('user_id')->nullable()->index()
                ->comment('ID пользователя-получателя в системе');

            $table->string('text', 500)
                ->comment('Текст уведомления (жесткое ограничение 500 символов)');

            $table->string('channel')
                ->comment('Канал отправки: email, telegram, sms');

            $table->timestamps();
            $table->softDeletes(); // Мягкое удаление для бизнес-сущности

            $table->comment('Бизнес-таблица истории уведомлений на стороне Laravel');
        });

        // 2. ТЕХНИЧЕСКАЯ ТАБЛИЦА: Чистый Transactional Outbox для Debezium
        // ВАЖНО: Никаких updates, statuses и soft deletes. Только INSERT.
        Schema::create('outbox_messages', function (Blueprint $table) {
            $table->uuid('id')->primary()
                ->comment('UUID сообщения, совпадающий с uuid в таблице notifications');

            $table->string('priority')->index()
                ->comment('Приоритет сообщения (high/low). Используется Debezium для маршрутизации в топики');

            $table->jsonb('payload')
                ->comment('Полный JSON-пакет данных сообщения (текст, получатель, канал) для отправки в Кафку');

            $table->timestamp('created_at')->useCurrent()->index()
                ->comment('Таймстамп создания записи для хронологического чтения CDC лога');

            $table->comment('Техническая append-only таблица паттерна Transactional Outbox для чтения Debezium');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
        Schema::dropIfExists('notifications');
    }
};
