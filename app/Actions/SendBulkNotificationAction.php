<?php

declare(strict_types=1);

namespace App\Actions;

use App\Collections\UserIdsCollection;
use App\DTO\BulkNotificationDTO;
use App\DTO\GeneratedMessageDTO;
use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Класс-действие для оркестрации и запуска процесса массовой рассылки.
 */
readonly class SendBulkNotificationAction
{
    public function __construct(
        private NotificationService $service
    ) {}

    /**
     * Выполняет генерацию идентификаторов, сборку DTO и передачу пакета в сервис.
     *
     * @param string $idempotencyKey
     * @param array<string, mixed> $payload
     * @return Collection<int, GeneratedMessageDTO>
     */
    public function execute(string $idempotencyKey, array $payload): Collection
    {
        $userIdsArray = (array) $payload['user_ids'];
        
        $messageIds = [];
        $mapping = collect();
        
        foreach ($userIdsArray as $userId) {
            $uuid = Str::uuid()->toString();
            $messageIds[] = $uuid;
            
            $mapping->push(new GeneratedMessageDTO(
                userId: (int) $userId,
                messageId: $uuid
            ));
        }

        // Переводим массив в специализированную коллекцию проекта
        $userIdsCollection = new UserIdsCollection($userIdsArray);

        $dto = new BulkNotificationDTO(
            idempotencyKey: $idempotencyKey,
            channel: NotificationChannel::from($payload['channel']),
            priority: isset($payload['priority']) ? NotificationPriority::from($payload['priority']) : NotificationPriority::LOW,
            text: (string) $payload['text'],
            userIds: $userIdsCollection,
            messageIds: $messageIds
        );

        $this->service->dispatchBulkNotification($dto);

        return $mapping;
    }
}
