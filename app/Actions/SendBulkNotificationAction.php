<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\BulkNotificationDTO;
use App\Services\NotificationService;

/**
 * Класс-действие для запуска процесса массовой рассылки уведомлений.
 */
readonly class SendBulkNotificationAction
{
    public function __construct(
        private NotificationService $service
    ) {}

    /**
     * Выполняет операцию валидации и передачи пакета уведомлений в сервис.
     */
    public function execute(BulkNotificationDTO $dto): void
    {
        $this->service->dispatchBulkNotification($dto);
    }
}
