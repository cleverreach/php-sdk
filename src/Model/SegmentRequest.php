<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class SegmentRequest implements RequestModelInterface
{
    /** @param list<array<string, mixed>> $rules */
    public function __construct(
        public readonly string $name,
        public readonly array $rules,
        public readonly string $description = ''
    ) {
    }

    public function toArray(): array {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'rules' => $this->rules,
        ];
    }
}
