<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

final class DebugService extends AbstractService
{
    public function ping(): string {
        return (string) $this->requestor->requestScalar('GET', 'debug/ping.json');
    }

    /** @return array<string, mixed>|list<array<string, mixed>> */
    public function validate(): array {
        return $this->requestor->request('GET', 'debug/validate.json');
    }

    /** @return array<string, mixed>|list<array<string, mixed>> */
    public function whoami(): array {
        return $this->requestor->request('GET', 'debug/whoami.json');
    }
}
