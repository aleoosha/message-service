<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\BulkNotificationDTO;
use App\Repositories\NotificationRepository;

class NotificationService
{
    public function __construct(
        private NotificationRepository $repository
    ) {}

    public function dispatchBulkNotification(BulkNotificationDTO $dto): void
    {
        $this->repository->saveBulk($dto);
    }
}
