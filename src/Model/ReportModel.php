<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class ReportModel extends AbstractModel
{
    /** @param array<string, mixed> $metrics */
    public function __construct(
        public readonly int $id,
        public readonly int $mailingId = 0,
        public readonly string $name = '',
        public readonly string $subject = '',
        public readonly string $status = '',
        public readonly array $metrics = []
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (int)($data['id'] ?? 0),
            (int)($data['mailing_id'] ?? 0),
            (string)($data['name'] ?? ''),
            (string)($data['subject'] ?? ''),
            (string)($data['status'] ?? ''),
            is_array($data['metrics'] ?? null) ? $data['metrics'] : []
        );
    }
}
