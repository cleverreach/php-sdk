<?php

declare(strict_types=1);

namespace CleverReach\SDK\Collection;

/**
 * @template T
 *
 * @implements \IteratorAggregate<int, T>
 */
abstract class AbstractCollection implements \IteratorAggregate, \Countable
{
    /**
     * @param list<T> $items
     */
    public function __construct(private readonly array $items) {
    }

    /**
     * @return \Traversable<int, T>
     */
    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->items);
    }

    public function count(): int {
        return count($this->items);
    }

    /**
     * @return list<T>
     */
    public function toArray(): array {
        return $this->items;
    }
}
