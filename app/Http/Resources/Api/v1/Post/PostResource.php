<?php

namespace App\Http\Resources\Api\v1\Post;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Post|static $this */
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'content' => $this->censored_content,
            'published_at' => $this->published_at,
            'author' => $this->whenLoaded(
                relationship: 'author',
                value: ['name' => $this->author->name],
            ),
        ];
    }
}
