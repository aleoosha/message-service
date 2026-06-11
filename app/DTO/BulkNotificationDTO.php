<?php

declare(strict_types=1);

namespace App\DTO;

use App\Collections\UserIdsCollection;
use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;

readonly class BulkNotificationDTO
{
    public function __construct(
        public string $idempotencyKey,
        public NotificationChannel $channel,
        public NotificationPriority $priority,
        public string $text,
        public UserIdsCollection $userIds,
    ) {}

    public static function fromRequest(array $data, string $idempotencyKey): self
    {
        $ints = array_map(fn($id) => (int) $id, $data['user_ids']);

        return new self(
            idempotencyKey: $idempotencyKey,
            channel: NotificationChannel::from($data['channel']),
            priority: NotificationPriority::from($data['priority'] ?? 'low'),
            text: $data['text'],
            userIds: new UserIdsCollection($ints),
        );
    }
}
