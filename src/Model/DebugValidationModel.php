<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class DebugValidationModel extends AbstractModel
{
    public function __construct(
        public readonly bool $valid,
        public readonly string $message = ''
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (bool)($data['valid'] ?? $data['success'] ?? false),
            (string)($data['message'] ?? $data['error'] ?? '')
        );
    }
}
