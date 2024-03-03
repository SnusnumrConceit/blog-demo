<?php

namespace App\Enums\User;

use App\Enums\Contracts\ArrayableEnum;
use App\Enums\Traits\ArrayableAccess;

enum StatusEnum: string implements ArrayableEnum
{
    use ArrayableAccess;

    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
    case ADMIN = 'admin';
    case EMAIL_VERIFICATION = 'email_verification';
}
