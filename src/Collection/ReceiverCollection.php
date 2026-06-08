<?php

declare(strict_types=1);

namespace CleverReach\SDK\Collection;

use CleverReach\SDK\Model\ReceiverModel;

/**
 * @extends AbstractCollection<ReceiverModel>
 */
final class ReceiverCollection extends AbstractCollection
{
    /**
     * @param list<array<string, mixed>> $rows
     */
    public static function fromArrayList(array $rows): self {
        return new self(array_map(
            static fn (array $row): ReceiverModel => ReceiverModel::fromArray($row),
            $rows
        ));
    }
}
