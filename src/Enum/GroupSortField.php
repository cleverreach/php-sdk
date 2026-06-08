<?php

declare(strict_types=1);

namespace CleverReach\SDK\Enum;

enum GroupSortField: string
{
    case Changed = 'changed';

    case Created = 'created';
}
