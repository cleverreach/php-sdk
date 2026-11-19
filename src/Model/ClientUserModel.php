<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class ClientUserModel extends AbstractModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly bool $isAdmin = false,
        public readonly string $language = '',
        public readonly string $login = '',
        public readonly int $created = 0,
        public readonly int $lastLogin = 0
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (int)($data['id'] ?? 0),
            (string)($data['email'] ?? ''),
            (bool)($data['isadmin'] ?? false),
            (string)($data['lang'] ?? ''),
            (string)($data['login'] ?? ''),
            (int)($data['created'] ?? 0),
            (int)($data['last_login'] ?? 0)
        );
    }
}
