<?php

declare(strict_types=1);

namespace CleverReach\SDK\Auth\Storage;

use CleverReach\SDK\Auth\Tokens;

final class MemoryTokenStorage implements TokenStorageInterface
{
    private ?Tokens $tokens = null;

    public function get(): ?Tokens {
        return $this->tokens;
    }

    public function set(Tokens $tokens): void {
        $this->tokens = $tokens;
    }

    public function delete(): void {
        $this->tokens = null;
    }
}
