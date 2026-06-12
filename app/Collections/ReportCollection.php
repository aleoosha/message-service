<?php

declare(strict_types=1);

namespace App\Collections;

use App\DTO\ReportDTO;

/**
 * Типизированная коллекция объектов ReportDTO.
 */
readonly class ReportCollection
{
    /**
     * @param  array<int, ReportDTO>  $items
     */
    public function __construct(
        private array $items = []
    ) {}

    /**
     * Преобразует коллекцию DTO в массив массивов для сериализации.
     *
     * @return array<int, array<string, string>>
     */
    public function toArray(): array
    {
        return array_map(fn (ReportDTO $dto) => $dto->toArray(), $this->items);
    }

    /**
     * Проверяет, пуста ли коллекция.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }
}
