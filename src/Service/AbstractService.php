<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Http\ApiRequestorInterface;
use CleverReach\SDK\Collection\ModelCollection;
use CleverReach\SDK\Http\ScalarApiRequestorInterface;
use CleverReach\SDK\Model\AbstractModel;
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

    /**
     * @param array<string, mixed>|list<array<string, mixed>> $response
     * @return array<string, mixed>
     */
    protected function firstRow(array $response): array {
        return array_is_list($response) ? ($response[0] ?? []) : $response;
    }

    /**
     * @template T of AbstractModel
     * @param array<int|string, mixed> $response
     * @param class-string<T> $modelClass
     * @return ModelCollection<T>
     */
    protected function collection(array $response, string $modelClass): ModelCollection {
        $rows = array_values(array_filter($response, 'is_array'));

        return ModelCollection::fromArrayList($rows, $modelClass);
    }
}
