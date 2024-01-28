<?php

namespace App\Policies;

use App\Enums\User\StatusEnum;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Post $post): bool
    {
        if ($user->hasRole(StatusEnum::ADMIN)) return true;

        return $user->hasRole(StatusEnum::ACTIVE) && $user->id === $post->author_id;
    }

    /**
     * Разрешение на создание поста
     */
    public function create(User $user): bool
    {
        return $user->hasRole(StatusEnum::ADMIN) || $user->hasRole(StatusEnum::ACTIVE);
    }

    /**
     * Разрешение на обновление поста
     */
    public function update(User $user, Post $post): bool
    {
        if ($user->hasRole(StatusEnum::ADMIN)) return true;

        return $user->hasRole(StatusEnum::ACTIVE) && $user->id === $post->author_id;
    }

    /**
     * Разрешение на удаление поста
     */
    public function delete(User $user, Post $post): bool
    {
        if ($user->hasRole(StatusEnum::ADMIN)) return true;

        return $user->hasRole(StatusEnum::ACTIVE) && $user->id === $post->author_id;
    }

    /**
     * Разрешение на просмотр поста на сайте
     *
     * @param User|null $user
     * @param Post $post
     *
     * @return bool
     */
    public function sitePostShow(?User $user, Post $post): bool
    {
        if(! $user) return $post->isPublic();

        if ($user->hasRole(StatusEnum::ADMIN)) return true;

        if ($post->isPrivate()) return false;

        return $user->hasRole(StatusEnum::ACTIVE);
    }
}
