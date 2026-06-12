<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkNotificationRequest;
use App\Actions\SendBulkNotificationAction;
use App\DTO\GeneratedMessageDTO;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Принимает пакет уведомлений и делегирует обработку бизнес-слою.
     */
    public function sendBulk(BulkNotificationRequest $request, SendBulkNotificationAction $action): JsonResponse
    {
        $generatedCollection = $action->execute(
            idempotencyKey: (string) $request->header('X-Idempotency-Key'),
            payload: $request->validated()
        );

        $items = $generatedCollection->map(fn (GeneratedMessageDTO $item) => [
            'user_id' => $item->userId,
            'message_id' => $item->messageId,
        ])->toArray();

        return $this->success(
            message: 'Bulk notifications accepted for delivery',
            data: [
                'items' => $items
            ],
            code: 202
        );
    }
}
