<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class FormSendRequest implements RequestModelInterface
{
    /** @param array<string, mixed> $doiData */
    public function __construct(
        public readonly string $email,
        public readonly array $doiData = []
    ) {
    }

    public function toArray(): array {
        return [
            'email' => $this->email,
            'doidata' => $this->doiData,
        ];
    }
}
