<?php

declare(strict_types=1);

namespace CleverReach\SDK\Enum;

enum ReceiverType: string
{
    case All = 'all';
    case Active = 'active';
    case Inactive = 'inactive';
    case Bounce = 'bounce';
}
