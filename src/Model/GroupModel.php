<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class GroupModel extends AbstractModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly bool $locked,
        public readonly bool $backup,
        public readonly string $receiverInfo,
        public readonly int $stamp,
        public readonly int $lastMailing,
        public readonly int $lastChanged
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static {
        return new self(
            id: (int) ($data['id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            locked: (bool) ($data['locked'] ?? false),
            backup: (bool) ($data['backup'] ?? false),
            receiverInfo: (string) ($data['receiver_info'] ?? ''),
            stamp: (int) ($data['stamp'] ?? 0),
            lastMailing: (int) ($data['last_mailing'] ?? 0),
            lastChanged: (int) ($data['last_changed'] ?? 0)
        );
    }
}
