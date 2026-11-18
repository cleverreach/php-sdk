<?php

declare(strict_types=1);

namespace CleverReach\SDK\Http;

interface ApiRequestorInterface
{
    /**
     * @param array<string, null|scalar> $query
     * @param null|array<string, mixed>|list<array<string, mixed>>  $json
     *
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function request(
        string $method,
        string $uri,
        array $query = [],
        ?array $json = null
    ): array;

    /**
     * @param array<string, null|scalar> $query
     * @param null|array<string, mixed>|list<array<string, mixed>>  $json
     */
    public function requestScalar(
        string $method,
        string $uri,
        array $query = [],
        ?array $json = null
    ): mixed;
}
