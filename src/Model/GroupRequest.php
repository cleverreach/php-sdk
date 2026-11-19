<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class GroupRequest implements RequestModelInterface
{
    public function __construct(
        public readonly string $name,
        public readonly bool $backup = false,
        public readonly bool $locked = false,
        public readonly string $receiverInfo = ''
    ) {
    }

    public function toArray(): array {
        return [
            'name' => $this->name,
            'backup' => $this->backup,
            'locked' => $this->locked,
            'receiver_info' => $this->receiverInfo,
        ];
    }
}
