<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class AttributeRequest implements RequestModelInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $type = 'text',
        public readonly string $description = ''
    ) {
    }

    public function toArray(): array {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
        ];
    }
}
