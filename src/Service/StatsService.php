<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Model\GroupStatsModel;

final class StatsService extends AbstractService
{
    public function group(int $groupId): GroupStatsModel {
        $response = $this->requestor->request('GET', "groups/{$groupId}/stats");

        return GroupStatsModel::fromArray($this->firstRow($response));
    }

    public function countByTag(string $tag, ?int $groupId = null): int {
        return (int)$this->requestScalar('GET', 'tags/count', [
            'tag' => $tag,
            'group_id' => $groupId,
        ]);
    }
}
