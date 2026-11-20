<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class WebhookEventModel extends AbstractModel
{
    public function __construct(
        public readonly string $callToken,
        public readonly string $event = '',
        public readonly string $condition = '',
        public readonly string $url = ''
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (string)($data['call_token'] ?? $data['callToken'] ?? ''),
            (string)($data['event'] ?? ''),
            (string)($data['condition'] ?? ''),
            (string)($data['url'] ?? '')
        );
    }
}
