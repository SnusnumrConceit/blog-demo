<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface VisibilityModel
{
    /**
     * Выборка по публичным категориям
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopePublic(Builder $query): Builder;

    /**
     * Выборка по защищённым категориям
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeProtected(Builder $query): Builder;

    /**
     * Выборка по скрытым категориям
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopePrivate(Builder $query): Builder;

    /**
     * Публичная
     *
     * @return bool
     */
    public function isPublic(): bool;

    /**
     * Скрыта от гостей
     *
     * @return bool
     */
    public function isProtected(): bool;

    /**
     * Приватная
     *
     * @return bool
     */
     public function isPrivate(): bool;
}
