<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Http\ApiRequestorInterface;
use CleverReach\SDK\Http\ScalarApiRequestorInterface;
use LogicException;

abstract class AbstractService
{
    public function __construct(protected readonly ApiRequestorInterface $requestor) {
    }

    /**
     * @param array<string, null|scalar> $query
     * @param null|array<string, mixed>|list<array<string, mixed>> $json
     */
    protected function requestScalar(
        string $method,
        string $uri,
        array $query = [],
        ?array $json = null
    ): mixed {
        if (!$this->requestor instanceof ScalarApiRequestorInterface) {
            throw new LogicException('The configured requestor does not support scalar responses.');
        }

        return $this->requestor->requestScalar($method, $uri, $query, $json);
    }
}
