<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Выполняет создание таблиц базы данных с подробным комментированием полей.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
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
            $table->softDeletes();

            $table->comment('Бизнес-таблица истории уведомлений на стороне Laravel');
        });

        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary()
                ->comment('Уникальный идентификатор события транзакционного лога Outbox');

            $table->string('aggregatetype')
                ->comment('Тип бизнес-агрегата для группировки событий в Debezium (например, Notification)');

            $table->string('aggregateid')
                ->comment('Связующий идентификатор конкретного экземпляра агрегата');

            $table->string('type')
                ->comment('Тип события, используемый Debezium как маркер динамической маршрутизации (high/low)');

            $table->jsonb('payload')
                ->comment('Полное сериализованное тело сообщения (JSON) для передачи в шину событий');

            $table->timestamp('created_at')->useCurrent()->index()
                ->comment('Штамп времени создания записи в логе Outbox');

            $table->comment('Стандартная append-only таблица Debezium Outbox');
        });
    }

    /**
     * Выполняет откат миграции, удаляя созданные таблицы.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
        Schema::dropIfExists('notifications');
    }
};
