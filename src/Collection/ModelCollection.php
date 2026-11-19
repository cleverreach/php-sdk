<?php

declare(strict_types=1);

namespace CleverReach\SDK\Collection;

use CleverReach\SDK\Model\AbstractModel;

/**
 * @template T of AbstractModel
 * @extends AbstractCollection<T>
 */
final class ModelCollection extends AbstractCollection
{
    /**
     * @template TModel of AbstractModel
     * @param list<array<string, mixed>> $rows
     * @param class-string<TModel> $modelClass
     * @return ModelCollection<TModel>
     */
    public static function fromArrayList(array $rows, string $modelClass): self {
        return new self(array_map(
            static fn (array $row): AbstractModel => $modelClass::fromArray($row),
            $rows
        ));
    }
}
