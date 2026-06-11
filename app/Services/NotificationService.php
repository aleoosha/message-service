<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\BulkNotificationDTO;
use App\Repositories\Contracts\NotificationRepositoryInterface;

/**
 * Сервисный слой для оркестрации бизнес-логики системы уведомлений.
 */
readonly class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $repository
    ) {}

    /**
     * Запускает процесс обработки и персистентного сохранения пакета уведомлений.
     */
    public function dispatchBulkNotification(BulkNotificationDTO $dto): void
    {
        $this->repository->saveBulk($dto);
    }
}
