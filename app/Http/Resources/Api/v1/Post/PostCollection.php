<?php

namespace App\Http\Resources\Api\v1\Post;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PostCollection extends ResourceCollection
{
    public $resource = PostResource::class;

    public static $wrap = 'posts';

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Post|static $this */
        return $this->collect($this->items())->toArray();
    }
}
