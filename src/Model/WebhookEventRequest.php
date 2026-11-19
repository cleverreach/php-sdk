<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class WebhookEventRequest implements RequestModelInterface
{
    public function __construct(
        public readonly string $url,
        public readonly string $event,
        public readonly string $condition,
        public readonly string $verify
    ) {
    }

    public function toArray(): array {
        return [
            'url' => $this->url,
            'event' => $this->event,
            'condition' => $this->condition,
            'verify' => $this->verify,
        ];
    }
}
