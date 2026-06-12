<?php

declare(strict_types=1);

namespace App\DTO;

use App\Collections\UserIdsCollection;
use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;

readonly class BulkNotificationDTO
{
    /**
     * @param  array<int, string>  $messageIds
     */
    public function __construct(
        public string $idempotencyKey,
        public NotificationChannel $channel,
        public NotificationPriority $priority,
        public string $text,
        public UserIdsCollection $userIds,
        public array $messageIds
    ) {}
}
