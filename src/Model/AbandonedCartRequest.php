<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class AbandonedCartRequest implements RequestModelInterface
{
    /** @param list<mixed> $items */
    public function __construct(
        public readonly string $currency,
        public readonly string $storeId,
        public readonly float $total,
        public readonly float $vat,
        public readonly array $items,
        public readonly string $returnUrl
    ) {
        foreach ($items as $item) {
            if (!$item instanceof AbandonedCartItemRequest) {
                throw new \InvalidArgumentException('Cart items must be AbandonedCartItemRequest instances.');
            }
        }
    }

    public function toArray(): array {
        return [
            'currency' => $this->currency,
            'storeid' => $this->storeId,
            'total' => $this->total,
            'vat' => $this->vat,
            'cart_items' => array_map(
                static fn (AbandonedCartItemRequest $item): array => $item->toArray(),
                $this->items
            ),
            'return_url' => $this->returnUrl,
        ];
    }
}
