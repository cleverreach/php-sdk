<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class BlacklistRequest implements RequestModelInterface
{
    public function __construct(
        public readonly string $email,
        public readonly int $groupId
    ) {
    }

    public function toArray(): array {
        return [
            'email' => $this->email,
            'group_id' => $this->groupId,
        ];
    }
}
