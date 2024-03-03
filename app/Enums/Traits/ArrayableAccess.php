<?php

namespace App\Enums\Traits;

use Illuminate\Support\Arr;

trait ArrayableAccess
{
    /** @inheritDoc */
    public static function getValues(): array
    {
        $cases = static::cases();

        return array_column($cases, 'value');
    }

    /** @inheritDoc */
    public static function getRandomValue(): mixed
    {
        return Arr::random(static::getValues());
    }
}
