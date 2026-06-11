<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\SendBulkNotificationAction;
use App\DTO\BulkNotificationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkNotificationRequest;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function sendBulk(
        BulkNotificationRequest $request,
        SendBulkNotificationAction $action
    ): JsonResponse {
        $idempotencyKey = (string) $request->header('X-Idempotency-Key');

        $dto = BulkNotificationDTO::fromRequest($request->validated(), $idempotencyKey);

        $action->execute($dto);

        return response()->json([
            'status' => 'success',
            'message' => 'Bulk notifications accepted for delivery',
        ], 202);
    }
}
