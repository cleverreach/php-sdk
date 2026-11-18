<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

final class SegmentsService extends AbstractService
{
    /** @return array<string, mixed>|list<array<string, mixed>> */
    public function all(int $groupId): array {
        return $this->requestor->request('GET', "groups.json/{$groupId}/filters");
    }

    /**
     * @param array<string, mixed> $segment
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function create(int $groupId, array $segment): array {
        return $this->requestor->request('POST', "groups.json/{$groupId}/filters", [], $segment);
    }

    /**
     * @param array<string, mixed> $segment
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function update(int $groupId, int|string $segmentId, array $segment): array {
        return $this->requestor->request('PUT', "groups.json/{$groupId}/filters/{$segmentId}", [], $segment);
    }

    public function delete(int $groupId, int|string $segmentId): void {
        $this->requestor->request('DELETE', "groups.json/{$groupId}/filters/{$segmentId}");
    }
}
