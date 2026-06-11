<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * Контракт для чтения аналитических отчетов.
 */
interface ReportRepositoryInterface
{
    /**
     * Получает историю статусов по получателю.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByRecipient(string $recipient): array;
}
