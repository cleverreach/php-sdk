<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class AbandonedCartAutomationRequest implements RequestModelInterface
{
    /** @param array<string, mixed> $settings */
    public function __construct(
        public readonly string $name,
        public readonly string $storeId,
        public readonly string $integration,
        public readonly array $settings = []
    ) {
    }

    public function toArray(): array {
        return [
            'name' => $this->name,
            'storeid' => $this->storeId,
            'integration' => $this->integration,
            'settings' => $this->settings,
        ];
    }
}
