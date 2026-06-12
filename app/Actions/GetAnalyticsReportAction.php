<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ReportDTO;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Класс-действие для получения аналитических отчетов с курсорной пагинацией.
 */
readonly class GetAnalyticsReportAction
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository
    ) {}

    /**
     * Выполняет выборку и рассчитывает метаданные курсора.
     *
     * @param string $recipient
     * @param int $limit
     * @param string|null $cursor
     * @return Collection<string, mixed>|null
     */
    public function execute(string $recipient, int $limit = 15, ?string $cursor = null): ?Collection
    {
        $reports = $this->reportRepository->getByRecipient($recipient, $limit, $cursor);

        if ($reports->isEmpty()) {
            return null;
        }

        $reportsArray = $reports->all();

        $items = array_map(fn (ReportDTO $report) => [
            'message_id' => $report->messageId,
            'recipient'  => $report->recipient,
            'status'     => $report->status,
            'updated_at' => $report->updatedAt,
        ], $reportsArray);

        $lastItem = end($items);
        $nextCursor = count($items) === $limit ? $lastItem['updated_at'] : null;

        return collect([
            'items' => $items,
            'meta' => [
                'limit' => $limit,
                'next_cursor' => $nextCursor,
            ]
        ]);
    }
}
