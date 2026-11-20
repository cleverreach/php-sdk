<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Model\SyncSettingsRequest;

final class SyncSettingsService extends AbstractService
{
    public function update(SyncSettingsRequest $settings): void {
        $this->requestor->request('POST', 'oauth/settings', [], $settings->toArray());
    }
}
