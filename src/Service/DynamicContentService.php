<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Collection\ModelCollection;
use CleverReach\SDK\Model\DynamicContentModel;
use CleverReach\SDK\Model\DynamicContentRequest;

final class DynamicContentService extends AbstractService
{
    /** @return ModelCollection<DynamicContentModel> */
    public function all(): ModelCollection {
        return $this->collection($this->requestor->request('GET', 'mycontent'), DynamicContentModel::class);
    }

    public function create(DynamicContentRequest $content): DynamicContentModel {
        $response = $this->requestor->request('POST', 'mycontent', [], $content->toArray());

        return DynamicContentModel::fromArray($this->firstRow($response));
    }

    public function update(int|string $id, DynamicContentRequest $content): DynamicContentModel {
        $response = $this->requestor->request('PUT', "mycontent/{$id}", [], $content->toArray());

        return DynamicContentModel::fromArray($this->firstRow($response));
    }

    public function delete(int|string $id): void {
        $this->requestor->request('DELETE', "mycontent/{$id}");
    }
}
