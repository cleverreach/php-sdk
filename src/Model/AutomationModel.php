<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class AutomationModel extends AbstractModel
{
    public function __construct(
        public readonly string $id,
        public readonly int $clientId,
        public readonly string $name,
        public readonly string $description = '',
        public readonly string $type = '',
        public readonly int $lastExecuted = 0,
        public readonly bool $active = false,
        public readonly int $created = 0
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (string)($data['id'] ?? ''),
            (int)($data['clientID'] ?? $data['client_id'] ?? 0),
            (string)($data['name'] ?? ''),
            (string)($data['description'] ?? ''),
            (string)($data['type'] ?? ''),
            (int)($data['lastExecuted'] ?? 0),
            (bool)($data['active'] ?? false),
            (int)($data['created'] ?? 0)
        );
    }
}
