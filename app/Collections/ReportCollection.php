<?php

declare(strict_types=1);

namespace App\Collections;

use App\DTO\ReportDTO;
use Illuminate\Support\Collection;

/**
 * Коллекция объектов аналитических отчетов.
 *
 * @extends Collection<int, ReportDTO>
 */
class ReportCollection extends Collection
{
    /**
     * @param  array<int, ReportDTO>  $items
     */
    public function __construct(array $items = [])
    {
        parent::__construct($items);
    }
}
