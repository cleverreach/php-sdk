<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

final class AttributesService extends AbstractService
{
    /** @return array<string, mixed>|list<array<string, mixed>> */
    public function all(): array {
        return $this->requestor->request('GET', 'attributes.json');
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function create(array $field): array {
        return $this->requestor->request('POST', 'attributes.json', [], $field);
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function update(int|string $id, array $field): array {
        return $this->requestor->request('PUT', "attributes.json/{$id}", [], $field);
    }
}
