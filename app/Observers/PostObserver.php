<?php

namespace App\Observers;

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
}
