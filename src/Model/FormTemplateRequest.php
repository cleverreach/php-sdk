<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class FormTemplateRequest implements RequestModelInterface
{
    /** @param array<string, mixed> $settings */
    public function __construct(
        public readonly string $type,
        public readonly string $name,
        public readonly array $settings = []
    ) {
    }

    public function toArray(): array {
        return array_merge($this->settings, ['name' => $this->name]);
    }
}
