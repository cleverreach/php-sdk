<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

interface RequestModelInterface
{
    /** @return array<string, mixed> */
    public function toArray(): array;
}
