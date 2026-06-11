<?php

declare(declare_types=1);

namespace App\Actions;

use App\DTO\BulkNotificationDTO;
use App\Services\NotificationService;

class SendBulkNotificationAction
{
    public function __construct(
        private NotificationService $service
    ) {}

    public function execute(BulkNotificationDTO $dto): void
    {
        $this->service->dispatchBulkNotification($dto);
    }
}
