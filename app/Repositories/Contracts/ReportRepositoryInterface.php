<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Collections\ReportCollection;

interface ReportRepositoryInterface
{
    /**
     * Получает коллекцию DTO отчетов из ClickHouse с поддержкой курсорной пагинации.
     */
    public function getByRecipient(string $recipient, int $limit = 15, ?string $nextCursor = null): ReportCollection;
}
