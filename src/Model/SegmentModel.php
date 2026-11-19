<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class SegmentModel extends AbstractModel
{
    /** @param list<array<string, mixed>> $rules */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description = '',
        public readonly array $rules = []
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (int)($data['id'] ?? 0),
            (string)($data['name'] ?? ''),
            (string)($data['description'] ?? ''),
            array_values(array_filter($data['rules'] ?? [], 'is_array'))
        );
    }
}
