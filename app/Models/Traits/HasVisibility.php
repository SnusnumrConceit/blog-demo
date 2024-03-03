<?php

namespace App\Models\Traits;

use App\Enums\PrivacyEnum;
use Illuminate\Database\Eloquent\Builder;

trait HasVisibility
{
    /** @inheritDoc */
    public function scopePublic(Builder $query): Builder
    {
        return $query->whereNull('privacy');
    }

    /** @inheritDoc */
    public function scopeProtected(Builder $query): Builder
    {
        return $query->where('privacy', PrivacyEnum::PROTECTED->value);
    }

    /** @inheritDoc */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('privacy', PrivacyEnum::PRIVATE->value);
    }

    /** @inheritDoc */
    public function isPublic(): bool
    {
        return is_null($this->privacy);
    }

    /** @inheritDoc */
    public function isProtected(): bool
    {
        return $this->privacy === PrivacyEnum::PROTECTED->value;
    }

    /** @inheritDoc */
    public function isPrivate(): bool
    {
        return $this->privacy === PrivacyEnum::PRIVATE->value;
    }
}
