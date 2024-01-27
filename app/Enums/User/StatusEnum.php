<?php

namespace App\Enums\User;

use App\Enums\BaseEnum;

class StatusEnum extends BaseEnum
{
    const ACTIVE = 'active';
    const BLOCKED = 'blocked';
    const ADMIN = 'admin';
    const EMAIL_VERIFICATION = 'email_verification';
}
