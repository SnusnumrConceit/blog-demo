<?php

namespace App\Enums;

use Illuminate\Support\Arr;
use ReflectionClass;

class BaseEnum
{
    /**
     * Список значений
     *
     * @return array
     */
    public static function getValues(): array
    {
        return (new ReflectionClass(static::class))->getConstants();
    }

    /**
     * Список ключей
     *
     * @return array
     */
    public static function getKeys(): array
    {
        return array_keys(static::getValues());
    }

    /*
     * Рандомное значение
     *
     * @return mixed
     */
    public static function getRandomValue(): mixed
    {
        return Arr::random(static::getValues());
    }

    /**
     * Рандомный ключ
     *
     * @return string
     */
    public static function getRandomKey(): string
    {
        return Arr::random(static::getKeys());
    }
}
