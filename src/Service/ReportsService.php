<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Model\ReportModel;

final class ReportsService extends AbstractService
{
    public function get(int|string $mailingId): ReportModel {
        $response = $this->requestor->request('GET', "reports/{$mailingId}");

        return ReportModel::fromArray($this->firstRow($response));
    }
}
