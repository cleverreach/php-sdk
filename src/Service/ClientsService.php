<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Collection\ModelCollection;
use CleverReach\SDK\Model\ClientUserModel;
use CleverReach\SDK\Model\PaymentPlanModel;

final class ClientsService extends AbstractService
{
    public function paymentPlan(int|string $clientId): PaymentPlanModel {
        $response = $this->requestor->request('GET', "clients/{$clientId}/plan");

        return PaymentPlanModel::fromArray($this->firstRow($response));
    }

    public function activeReceiversCount(int|string $clientId): int {
        return (int)$this->requestScalar('GET', "clients/{$clientId}/activereceivercount");
    }

    /** @return ModelCollection<ClientUserModel> */
    public function users(int|string $clientId): ModelCollection {
        return $this->collection($this->requestor->request('GET', "clients/{$clientId}/users"), ClientUserModel::class);
    }
}
