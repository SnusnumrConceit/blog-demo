<?php

namespace App\Enums\Contracts;

interface ArrayableEnum
{
    /**
     * Список значений
     *
     * @return array
     */
    public static function getValues(): array;

    /*
 * Рандомное значение
 *
 * @return mixed
 */
    public static function getRandomValue(): mixed;
}
