<?php

declare(strict_types=1);

namespace CleverReach\SDK\Service;

use CleverReach\SDK\Http\ApiRequestorInterface;

abstract class AbstractService
{
    public function __construct(protected readonly ApiRequestorInterface $requestor) {
    }
}
