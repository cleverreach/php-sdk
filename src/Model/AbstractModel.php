<?php

declare(strict_types=1);

namespace CleverReach\SDK\Model;

abstract class AbstractModel
{
    /**
     * Erstellt eine Model-Instanz aus einem assoziativen Array.
     *
     * @param array<string, mixed> $data
     */
    abstract public static function fromArray(array $data): static;
}
