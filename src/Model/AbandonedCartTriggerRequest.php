<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class AbandonedCartTriggerRequest implements RequestModelInterface
{
    public function __construct(
        public readonly AbandonedCartRequest $cart,
        public readonly string $groupId,
        public readonly string $poolId
    ) {
    }

    public function toArray(): array {
        return [
            'cart' => $this->cart->toArray(),
            'groupid' => $this->groupId,
            'poolid' => $this->poolId,
        ];
    }
}
