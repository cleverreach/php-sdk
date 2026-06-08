<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class ReceiverModel extends AbstractModel
{
    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $globalAttributes
     */
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly int $imported,
        public readonly int $bounced,
        public readonly int $groupId,
        public readonly int $activated,
        public readonly int $registered,
        public readonly int $deactivated,
        public readonly string $lastIp,
        public readonly string $lastLocation,
        public readonly string $lastClient,
        public readonly int $points,
        public readonly int $stars,
        public readonly string $source,
        public readonly array $attributes,
        public readonly array $globalAttributes,
        public readonly bool $active,
        public readonly float $conversionRate,
        public readonly float $openRate,
        public readonly float $clickRate,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static {
        return new self(
            id: (int) ($data['id'] ?? 0),
            email: (string) ($data['email'] ?? ''),
            imported: (int) ($data['imported'] ?? 0),
            bounced: (int) ($data['bounced'] ?? 0),
            groupId: (int) ($data['group_id'] ?? 0),
            activated: (int) ($data['activated'] ?? 0),
            registered: (int) ($data['registered'] ?? 0),
            deactivated: (int) ($data['deactivated'] ?? 0),
            lastIp: (string) ($data['last_ip'] ?? ''),
            lastLocation: (string) ($data['last_location'] ?? ''),
            lastClient: (string) ($data['last_client'] ?? ''),
            points: (int) ($data['points'] ?? 0),
            stars: (int) ($data['stars'] ?? 0),
            source: (string) ($data['source'] ?? ''),
            attributes: is_array($data['attributes'] ?? null) ? $data['attributes'] : [],
            globalAttributes: is_array($data['global_attributes'] ?? null) ? $data['global_attributes'] : [],
            active: filter_var($data['active'] ?? false, FILTER_VALIDATE_BOOLEAN),
            conversionRate: (float) ($data['conversion_rate'] ?? 0.0),
            openRate: (float) ($data['open_rate'] ?? 0.0),
            clickRate: (float) ($data['click_rate'] ?? 0.0)
        );
    }
}
