<?php

namespace App\Observers;

use App\Enums\Post\PrivacyEnum;
use App\Models\Post;
use Illuminate\Support\Str;

class PostObserver
{
    /**
     * @param Post $post
     *
     * @return void
     */
    public function saving(Post $post): void
    {
        if ($post->isDirty('title')) {
            $post->slug = Str::slug(title: $post->title, language: 'ru');
        }
    }

    /**
     * @param Post $post
     *
     * @return void
     */
    public function creating(Post $post): void
    {
        if ($post->published_at) {
            $post->privacy = PrivacyEnum::PRIVATE;
        }

        $post->published_at ??= now();
    }
}
