<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер для вывода аналитических отчетов и статусов доставки из ClickHouse.
 */
class ReportController extends Controller
{
    public function __construct(
        private ReportRepositoryInterface $repository
    ) {}

    /**
     * Возвращает историю статусов уведомлений по ключу получателя.
     */
    public function show(string $recipient): JsonResponse
    {
        $reports = $this->repository->getByRecipient($recipient);

        return $reports->isEmpty()
            ? $this->error('No reports found for this recipient', 404)
            : $this->success($reports->toArray());
    }
}
