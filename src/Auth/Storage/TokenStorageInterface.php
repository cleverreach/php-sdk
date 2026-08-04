<?php

declare(strict_types=1);

namespace CleverReach\SDK\Auth\Storage;

use CleverReach\SDK\Auth\Tokens;

interface TokenStorageInterface
{
    public function get(): ?Tokens;

    public function set(Tokens $tokens): void;

    public function delete(): void;
}
