<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

final class AutomationService extends AbstractService
{
    /**
     * @param array<string, mixed> $automation
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function createAbandonedCart(array $automation): array {
        return $this->requestor->request('POST', 'automation/createfromtemplate/abandonedcart.json', [], $automation);
    }

    /** @param array<string, mixed> $trigger */
    public function triggerAbandonedCart(array $trigger): void {
        $this->requestScalar('POST', 'trigger/abandonedcart.json', [], $trigger);
    }
}
