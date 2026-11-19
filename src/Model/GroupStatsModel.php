<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class GroupStatsModel extends AbstractModel
{
    public function __construct(
        public readonly int $totalCount = 0,
        public readonly int $inactiveCount = 0,
        public readonly int $activeCount = 0,
        public readonly int $bounceCount = 0,
        public readonly float $averagePoints = 0.0,
        public readonly float $quality = 0.0,
        public readonly int $time = 0,
        public readonly int $orderCount = 0
    ) {
    }

    public static function fromArray(array $data): static {
        return new self(
            (int)($data['total_count'] ?? 0),
            (int)($data['inactive_count'] ?? 0),
            (int)($data['active_count'] ?? 0),
            (int)($data['bounce_count'] ?? 0),
            (float)($data['avg_points'] ?? 0),
            (float)($data['quality'] ?? 0),
            (int)($data['time'] ?? 0),
            (int)($data['order_count'] ?? 0)
        );
    }
}
