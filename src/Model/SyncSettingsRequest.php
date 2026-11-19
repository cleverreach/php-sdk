<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class SyncSettingsRequest implements RequestModelInterface
{
    /** @param array<string, mixed> $settings */
    public function __construct(public readonly array $settings)
    {
    }

    public function toArray(): array {
        return $this->settings;
    }
}
