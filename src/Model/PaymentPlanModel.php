<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class PaymentPlanModel extends AbstractModel
{
    public function __construct(
        public readonly int $id = 0,
        public readonly string $name = '',
        public readonly int $flatrate = 0,
        public readonly int $emails = 0,
        public readonly int $receiverCap = 0,
        public readonly int $runtime = 0,
        public readonly float $price = 0.0,
        public readonly string $currency = ''
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (int)($data['id'] ?? 0),
            (string)($data['name'] ?? ''),
            (int)($data['flatrate'] ?? 0),
            (int)($data['emails'] ?? 0),
            (int)($data['receiver_cap'] ?? 0),
            (int)($data['runtime'] ?? 0),
            (float)($data['price'] ?? 0),
            (string)($data['currency'] ?? '')
        );
    }
}
