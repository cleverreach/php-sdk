<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

final class FormsService extends AbstractService
{
    /** @return array<string, mixed>|list<array<string, mixed>> */
    public function get(int|string $formId): array {
        return $this->requestor->request('GET', "forms.json/{$formId}");
    }

    /** @return array<string, mixed>|list<array<string, mixed>> */
    public function byGroup(int $groupId): array {
        return $this->requestor->request('GET', "groups.json/{$groupId}/forms");
    }

    /**
     * @param array<string, mixed> $typeData
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function createFromTemplate(int $groupId, string $type, array $typeData): array {
        return $this->requestor->request('POST', "forms.json/{$groupId}/createfromtemplate/{$type}", [], $typeData);
    }

    /**
     * @param array<string, mixed> $email
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function send(int|string $formId, string $type, array $email): array {
        return $this->requestor->request('POST', "forms.json/{$formId}/send/{$type}", [], $email);
    }
}
