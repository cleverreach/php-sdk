<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

final class SyncSettingsService extends AbstractService
{
    /** @param array<string, mixed> $settings */
    public function update(array $settings): void {
        $this->requestor->request('POST', 'oauth/settings.json', [], $settings);
    }
}
