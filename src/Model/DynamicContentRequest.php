<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

final class DynamicContentRequest implements RequestModelInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $url,
        public readonly string $password,
        public readonly bool $cors = true,
        public readonly string $icon = '',
        public readonly string $type = 'product'
    ) {
    }

    public function toArray(): array {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'password' => $this->password,
            'cors' => $this->cors,
            'icon' => $this->icon,
            'type' => $this->type,
        ];
    }
}
