<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\SendBulkNotificationAction;
use App\DTO\BulkNotificationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkNotificationRequest;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер для управления отправкой пакетов уведомлений через API.
 */
class NotificationController extends Controller
{
    /**
     * Принимает и обрабатывает запрос на массовую отправку уведомлений.
     */
    public function sendBulk(
        BulkNotificationRequest $request,
        SendBulkNotificationAction $action
    ): JsonResponse {
        $dto = BulkNotificationDTO::fromRequest($request->validated());

        $action->execute($dto);

        return $this->success(
            data: null,
            message: 'Bulk notifications accepted for delivery',
            code: 202
        );
    }
}
