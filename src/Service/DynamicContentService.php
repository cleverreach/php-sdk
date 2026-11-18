<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

final class DynamicContentService extends AbstractService
{
    /** @return array<string, mixed>|list<array<string, mixed>> */
    public function all(): array {
        return $this->requestor->request('GET', 'mycontent.json');
    }

    /**
     * @param array<string, mixed> $content
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function create(array $content): array {
        return $this->requestor->request('POST', 'mycontent.json', [], $content);
    }

    /**
     * @param array<string, mixed> $content
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function update(int|string $id, array $content): array {
        return $this->requestor->request('PUT', "mycontent.json/{$id}", [], $content);
    }

    public function delete(int|string $id): void {
        $this->requestor->request('DELETE', "mycontent.json/{$id}");
    }
}
