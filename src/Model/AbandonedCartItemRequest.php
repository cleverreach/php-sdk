<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class AbandonedCartItemRequest implements RequestModelInterface
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $amount,
        public readonly float $singlePrice,
        public readonly string $description = '',
        public readonly string $image = '',
        public readonly string $productUrl = '',
        public readonly string $sku = ''
    ) {
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount,
            'single_price' => $this->singlePrice,
            'description' => $this->description,
            'image' => $this->image,
            'product_url' => $this->productUrl,
            'sku' => $this->sku,
        ];
    }
}
