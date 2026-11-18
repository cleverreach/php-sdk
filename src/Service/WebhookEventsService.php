<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

final class WebhookEventsService extends AbstractService
{
    /**
     * @param array<string, mixed> $event
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function register(array $event): array {
        return $this->requestor->request('POST', 'hooks/eventhook', [], $event);
    }

    public function delete(string $condition, string $type): void {
        $this->requestor->request('DELETE', "hooks/eventhook/{$type}", ['condition' => $condition]);
    }
}
