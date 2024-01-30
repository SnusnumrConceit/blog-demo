<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\Category\CategoryCollection;
use App\Http\Resources\Api\v1\Category\CategoryResource;
use App\Models\Category;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Responsable;

class CategoryController extends Controller
{
    /**
     * Список публичных категорий
     *
     * @return Responsable
     */
    public function index(): Responsable
    {
        return new CategoryCollection(Category::public()->simplePaginate(15, ['slug', 'name']));
    }

    /**
     * Информация о публичной категории с постами
     *
     * @param Category $category
     *
     * @return Responsable
     * @throws AuthorizationException
     */
    public function show(Category $category): Responsable
    {
        $this->authorize('view', $category);

        $category->load(
            'publicPosts:id,title,slug,published_at',
            'posts.author:id,name'
        );

        return new CategoryResource($category);
    }
}
