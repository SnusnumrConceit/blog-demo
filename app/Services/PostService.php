<?php

namespace App\Services;

use App\Enums\User\StatusEnum;
use App\Models\Post;
use App\Models\User;

class PostService
{
    public static function incrementView(Post $post, ?User $user): void
    {
        if (! $user || $user?->hasRole(StatusEnum::ADMIN->value)) return;

        if ($post->views()->where('user_id', $user->id)->exists()) {
            return;
        }

        $post->views()->create(['user_id' => $user->id]);
    }
}
