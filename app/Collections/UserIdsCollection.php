<?php

declare(strict_types=1);

namespace App\Collections;

use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, int>
 */
readonly class UserIdsCollection implements Countable, IteratorAggregate
{
    /**
     * @param  int[]  $ids
     */
    private array $ids;

    /**
     * @param  mixed[]  $ids
     */
    public function __construct(array $ids)
    {
        $validated = [];

        foreach ($ids as $id) {
            if (! is_int($id)) {
                throw new InvalidArgumentException('All elements in UserIdsCollection must be integers.');
            }
            $validated[] = $id;
        }

        $this->ids = $validated;
    }

    /**
     * Возвращает сырой массив интов
     *
     * @return int[]
     */
    public function toArray(): array
    {
        return $this->ids;
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->ids);
    }

    public function count(): int
    {
        return count($this->ids);
    }
}
