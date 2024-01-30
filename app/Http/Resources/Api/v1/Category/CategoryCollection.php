<?php

namespace App\Http\Resources\Api\v1\Category;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryCollection extends ResourceCollection
{
    public static $wrap = 'categories';

    public $resource = CategoryResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collect($this->items())->toArray();
    }
}
