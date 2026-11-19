<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

final class ClientsService extends AbstractService
{
    /** @return array<string, mixed>|list<array<string, mixed>> */
    public function paymentPlan(int|string $clientId): array {
        return $this->requestor->request('GET', "clients.json/{$clientId}/plan");
    }

    public function activeReceiversCount(int|string $clientId): mixed {
        return $this->requestScalar('GET', "clients.json/{$clientId}/receivercount");
    }

    /** @return array<string, mixed>|list<array<string, mixed>> */
    public function users(int|string $clientId): array {
        return $this->requestor->request('GET', "clients.json/{$clientId}/users");
    }
}
