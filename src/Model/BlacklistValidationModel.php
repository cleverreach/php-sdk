<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class BlacklistValidationModel extends AbstractModel
{
    /** @param list<string> $invalidEmails */
    public function __construct(
        public readonly bool $valid,
        public readonly array $invalidEmails = []
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (bool)($data['valid'] ?? $data['success'] ?? false),
            array_values(array_filter($data['invalid_emails'] ?? $data['invalid'] ?? [], 'is_string'))
        );
    }
}
