<?php

declare(strict_types=1);

namespace CleverReach\SDK\Auth\Storage;

use CleverReach\SDK\Auth\Tokens;

final class FileTokenStorage implements TokenStorageInterface
{
    public function __construct(
        private readonly string $filePath
    ) {
    }

    public function get(): ?Tokens {
        if (!file_exists($this->filePath)) {
            return null;
        }

        $content = file_get_contents($this->filePath);
        if ($content === false || $content === '') {
            return null;
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                return null;
            }

            return Tokens::fromArray($data);
        } catch (\InvalidArgumentException|\JsonException) {
            return null;
        }
    }

    public function set(Tokens $tokens): void {
        $data = json_encode($tokens->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($this->filePath, $data, LOCK_EX);
    }

    public function delete(): void {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }
}
