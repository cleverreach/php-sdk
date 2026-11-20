<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Collection\ModelCollection;
use CleverReach\SDK\Model\AttributeModel;
use CleverReach\SDK\Model\AttributeRequest;

final class AttributesService extends AbstractService
{
    /** @return ModelCollection<AttributeModel> */
    public function all(): ModelCollection {
        return $this->collection($this->requestor->request('GET', 'attributes'), AttributeModel::class);
    }

    public function create(AttributeRequest $field): AttributeModel {
        $response = $this->requestor->request('POST', 'attributes', [], $field->toArray());

        return AttributeModel::fromArray($this->firstRow($response));
    }

    public function update(int|string $id, AttributeRequest $field): AttributeModel {
        $response = $this->requestor->request('PUT', "attributes/{$id}", [], $field->toArray());

        return AttributeModel::fromArray($this->firstRow($response));
    }
}
