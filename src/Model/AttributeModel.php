<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class AttributeModel extends AbstractModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $type,
        public readonly string $description,
        public readonly ?string $defaultValue = null,
        public readonly ?string $previewValue = null
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (int)($data['id'] ?? 0),
            (string)($data['name'] ?? ''),
            (string)($data['type'] ?? ''),
            (string)($data['description'] ?? ''),
            isset($data['default_value']) ? (string)$data['default_value'] : null,
            isset($data['preview_value']) ? (string)$data['preview_value'] : null
        );
    }
}
