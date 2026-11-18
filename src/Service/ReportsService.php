<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

final class ReportsService extends AbstractService
{
    /** @return array<string, mixed>|list<array<string, mixed>> */
    public function get(int|string $mailingId): array {
        return $this->requestor->request('GET', "reports.json/{$mailingId}");
    }
}
