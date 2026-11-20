<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Collection\ModelCollection;
use CleverReach\SDK\Model\SegmentModel;
use CleverReach\SDK\Model\SegmentRequest;

final class SegmentsService extends AbstractService
{
    /** @return ModelCollection<SegmentModel> */
    public function all(int $groupId): ModelCollection {
        $response = $this->requestor->request('GET', "groups/{$groupId}/filters");

        return $this->collection($response, SegmentModel::class);
    }

    public function create(int $groupId, SegmentRequest $segment): SegmentModel {
        $response = $this->requestor->request('POST', "groups/{$groupId}/filters", [], $segment->toArray());

        return SegmentModel::fromArray($this->firstRow($response));
    }

    public function update(int $groupId, int|string $segmentId, SegmentRequest $segment): SegmentModel {
        $response = $this->requestor->request(
            'PUT',
            "groups/{$groupId}/filters/{$segmentId}",
            [],
            $segment->toArray()
        );

        return SegmentModel::fromArray($this->firstRow($response));
    }

    public function delete(int $groupId, int|string $segmentId): void {
        $this->requestor->request('DELETE', "groups/{$groupId}/filters/{$segmentId}");
    }
}
