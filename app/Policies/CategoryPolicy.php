<?php

namespace App\Policies;

use App\Enums\User\StatusEnum;
use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Category $category): bool
    {
        if (request()->isJson()) return $category->isPublic();

        if (! $user) return $category->isPublic();

        if ($user->isAdmin()) return true;

        return ! $category->isPrivate() && $user->hasRole(StatusEnum::ACTIVE->value);
    }
}
