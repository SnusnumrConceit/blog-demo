<?php

namespace App\Enums;

use App\Enums\Contracts\ArrayableEnum;
use App\Enums\Traits\ArrayableAccess;

enum PrivacyEnum: string implements ArrayableEnum
{
    use ArrayableAccess;

    case PROTECTED = 'protected';
    case PRIVATE = 'private';
}
