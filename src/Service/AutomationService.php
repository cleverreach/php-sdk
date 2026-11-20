<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Model\AbandonedCartAutomationRequest;
use CleverReach\SDK\Model\AbandonedCartTriggerRequest;
use CleverReach\SDK\Model\AutomationModel;

final class AutomationService extends AbstractService
{
    public function createAbandonedCart(AbandonedCartAutomationRequest $automation): AutomationModel {
        $response = $this->requestor->request(
            'POST',
            'automation/createfromtemplate/abandonedcart',
            [],
            $automation->toArray()
        );

        return AutomationModel::fromArray($this->firstRow($response));
    }

    public function triggerAbandonedCart(AbandonedCartTriggerRequest $trigger): int {
        return (int)$this->requestScalar('POST', 'trigger/abandonedcart', [], $trigger->toArray());
    }
}
