<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Model\WebhookEventModel;
use CleverReach\SDK\Model\WebhookEventRequest;

final class WebhookEventsService extends AbstractService
{
    private const ENDPOINT = 'https://rest.cleverreach.com/hooks/eventhook';

    public function register(WebhookEventRequest $event): WebhookEventModel {
        $response = $this->requestor->request('POST', self::ENDPOINT, [], $event->toArray());

        return WebhookEventModel::fromArray($this->firstRow($response));
    }

    public function delete(string $condition, string $type): void {
        $this->requestor->request('DELETE', self::ENDPOINT . '/' . $type, ['condition' => $condition]);
    }
}
