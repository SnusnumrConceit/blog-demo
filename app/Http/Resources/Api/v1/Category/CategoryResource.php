<?php

namespace App\Http\Resources\Api\v1\Category;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Category|static $this */
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'posts' => $this->whenLoaded(
                relationship: 'publicPosts',
                value: $this->publicPosts->each(fn (Post $post)
                    => $post->author = $this->whenLoaded(
                        relationship: 'publicPosts.author',
                        value: $post->author?->only(['name']),
                    )
                )
                    ->only(['slug', 'title', 'published_at',])
                    ->all(),
                default: []
            ),
        ];
    }
}
