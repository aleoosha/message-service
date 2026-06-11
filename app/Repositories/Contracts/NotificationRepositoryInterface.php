<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTO\BulkNotificationDTO;

/**
 * Контракт для сохранения пакетов уведомлений.
 */
interface NotificationRepositoryInterface
{
    /**
     * Сохраняет пакет уведомлений в постоянное хранилище.
     */
    public function saveBulk(BulkNotificationDTO $dto): void;
}
