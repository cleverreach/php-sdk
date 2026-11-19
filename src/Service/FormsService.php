<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Collection\ModelCollection;
use CleverReach\SDK\Model\FormModel;
use CleverReach\SDK\Model\FormSendRequest;
use CleverReach\SDK\Model\FormSendResult;
use CleverReach\SDK\Model\FormTemplateRequest;

final class FormsService extends AbstractService
{
    public function get(int|string $formId): FormModel {
        $response = $this->requestor->request('GET', "forms/{$formId}");

        return FormModel::fromArray($this->firstRow($response));
    }

    /** @return ModelCollection<FormModel> */
    public function byGroup(int $groupId): ModelCollection {
        return $this->collection($this->requestor->request('GET', "groups/{$groupId}/forms"), FormModel::class);
    }

    public function createFromTemplate(int $groupId, FormTemplateRequest $form): FormModel {
        $response = $this->requestor->request(
            'POST',
            "forms/{$groupId}/createfromtemplate/{$form->type}",
            [],
            $form->toArray()
        );

        return FormModel::fromArray($this->firstRow($response));
    }

    public function send(int|string $formId, string $type, FormSendRequest $request): FormSendResult {
        $response = $this->requestor->request('POST', "forms/{$formId}/send/{$type}", [], $request->toArray());

        return FormSendResult::fromArray($this->firstRow($response));
    }
}
