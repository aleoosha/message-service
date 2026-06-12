<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\GetAnalyticsReportAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetAnalyticsReportRequest;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * Возвращает аналитические отчеты по получателю.
     */
    public function show(
        string $recipient,
        GetAnalyticsReportRequest $request,
        GetAnalyticsReportAction $action
    ): JsonResponse {

        $result = $action->execute(
            recipient: $recipient,
            limit: $request->integer('limit', 15),
            cursor: $request->filled('cursor') ? $request->string('cursor')->toString() : null
        );

        if ($result === null) {
            return $this->error(
                message: 'No reports found for this recipient',
                code: 404
            );
        }

        return $this->success(
            message: 'Action completed successfully',
            data: $result->toArray(),
            code: 200
        );
    }
}
