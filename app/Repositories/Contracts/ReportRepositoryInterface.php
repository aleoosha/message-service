<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Collections\ReportCollection;

interface ReportRepositoryInterface
{
    /**
     * Получает коллекцию DTO отчетов по получателю.
     */
    public function getByRecipient(string $recipient): ReportCollection;
}
