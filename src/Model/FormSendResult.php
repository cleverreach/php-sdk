<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class FormSendResult extends AbstractModel
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message = ''
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (bool)($data['success'] ?? false),
            (string)($data['message'] ?? $data['error'] ?? '')
        );
    }
}
