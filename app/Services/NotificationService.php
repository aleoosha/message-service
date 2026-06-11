<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\BulkNotificationDTO;
use App\Repositories\NotificationRepository;

/**
 * Сервисный слой для оркестрации бизнес-логики системы уведомлений.
 */
readonly class NotificationService
{
    public function __construct(
        private NotificationRepository $repository
    ) {}

    /**
     * Запускает процесс обработки и персистентного сохранения пакета уведомлений.
     */
    public function dispatchBulkNotification(BulkNotificationDTO $dto): void
    {
        $this->repository->saveBulk($dto);
    }
}
