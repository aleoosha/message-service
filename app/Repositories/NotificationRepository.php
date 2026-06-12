<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\BulkNotificationDTO;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Репозиторий для управления персистентным состоянием уведомлений в базе данных PostgreSQL.
 */
class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * Выполняет атомарную запись пакета уведомлений в операционную и Outbox таблицы.
     */
    public function saveBulk(BulkNotificationDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $userIdsArray = collect($dto->userIds)->all();

            foreach ($userIdsArray as $index => $userId) {
                $notificationUuid = $dto->messageIds[$index];

                DB::table('notifications')->insert([
                    'uuid' => $notificationUuid,
                    'idempotency_key' => $dto->idempotencyKey.':'.$userId,
                    'user_id' => (int) $userId,
                    'text' => $dto->text,
                    'channel' => $dto->channel->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('outbox_messages')->insert([
                    'id' => $notificationUuid,
                    'aggregatetype' => 'Notification',
                    'aggregateid' => $notificationUuid,
                    'type' => $dto->priority->value,
                    'payload' => json_encode([
                        'id' => $notificationUuid,
                        'user_id' => (int) $userId,
                        'text' => $dto->text,
                        'channel' => $dto->channel->value,
                        'recipient' => (string) $userId,
                    ], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                ]);
            }
        });
    }
}
