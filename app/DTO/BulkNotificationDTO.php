<?php

declare(strict_types=1);

namespace App\DTO;

use App\Collections\UserIdsCollection;
use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;

/**
 * Объект переноса данных (DTO) для пакета массовой рассылки уведомлений.
 */
readonly class BulkNotificationDTO
{
    public function __construct(
        public string $idempotencyKey,
        public NotificationChannel $channel,
        public NotificationPriority $priority,
        public string $text,
        public UserIdsCollection $userIds,
    ) {}

    /**
     * Фабричный метод для сборки DTO из валидированных данных HTTP-запроса.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        $ints = array_map(fn ($id) => (int) $id, $data['user_ids']);

        return new self(
            idempotencyKey: $data['idempotency_key'],
            channel: NotificationChannel::from($data['channel']),
            priority: NotificationPriority::from($data['priority'] ?? 'low'),
            text: (string) $data['text'],
            userIds: new UserIdsCollection($ints),
        );
    }
}
