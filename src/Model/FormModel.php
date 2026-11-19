<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class FormModel extends AbstractModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $groupId = 0,
        public readonly string $type = ''
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (int)($data['id'] ?? 0),
            (string)($data['name'] ?? ''),
            (int)($data['group_id'] ?? $data['groups_id'] ?? 0),
            (string)($data['type'] ?? '')
        );
    }
}
