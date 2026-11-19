<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

final class StatsService extends AbstractService
{
    /** @return array<string, mixed>|list<array<string, mixed>> */
    public function group(int $groupId): array {
        return $this->requestor->request('GET', "groups.json/{$groupId}/stats");
    }

    public function countByTag(string $tag, ?int $groupId = null): int {
        $response = $this->requestScalar('GET', 'tags/count.json', [
            'tag' => $tag,
            'group_id' => $groupId,
        ]);

        return (int) $response;
    }
}
