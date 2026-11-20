<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Model\AccountModel;
use CleverReach\SDK\Model\DebugValidationModel;

final class DebugService extends AbstractService
{
    public function ping(): string {
        return (string)$this->requestScalar('GET', 'debug/ping');
    }

    public function whoami(): AccountModel {
        return AccountModel::fromArray($this->firstRow($this->requestor->request('GET', 'debug/whoami')));
    }

    public function validate(): DebugValidationModel {
        $response = $this->requestor->request('GET', 'debug/validate');

        return DebugValidationModel::fromArray($this->firstRow($response));
    }
}
