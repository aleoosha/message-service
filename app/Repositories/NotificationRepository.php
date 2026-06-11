<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\BulkNotificationDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationRepository
{
    /**
     * Сохраняет пакет уведомлений в бизнес-таблицу и таблицу Outbox.
     * 
     * Использование ACID транзакции гарантирует атомарность: данные
     * попадут в обе таблицы одновременно, либо транзакция полностью откатится.
     */
    public function saveBulk(BulkNotificationDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            foreach ($dto->userIds->toArray() as $userId) {
                $notificationUuid = Str::uuid()->toString();

                DB::table('notifications')->insert([
                    'uuid' => $notificationUuid,
                    'idempotency_key' => $dto->idempotencyKey . ':' . $userId,
                    'user_id' => $userId,
                    'text' => $dto->text,
                    'channel' => $dto->channel->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('outbox_messages')->insert([
                    'id' => $notificationUuid,
                    'priority' => $dto->priority->value,
                    'payload' => json_encode([
                        'id' => $notificationUuid,
                        'user_id' => $userId,
                        'text' => $dto->text,
                        'channel' => $dto->channel->value,
                    ], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                ]);
            }
        });
    }
}
