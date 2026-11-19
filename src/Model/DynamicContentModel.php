<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class DynamicContentModel extends AbstractModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $url,
        public readonly string $type,
        public readonly bool $cors = false,
        public readonly string $icon = ''
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (int)($data['id'] ?? 0),
            (string)($data['name'] ?? ''),
            (string)($data['url'] ?? ''),
            (string)($data['type'] ?? ''),
            (bool)($data['cors'] ?? false),
            (string)($data['icon'] ?? '')
        );
    }
}
