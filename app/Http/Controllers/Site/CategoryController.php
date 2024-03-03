<?php

namespace App\Http\Controllers\Site;

use App\Enums\PrivacyEnum;
use App\Enums\User\StatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    /**
     * Список категорий
     *
     * @return Factory|View
     */
    public function index(): Factory|View
    {
        $categories = Category::query()->withCount('posts')
            ->whereNull('privacy')
            ->when(
                value: auth()->user()?->hasRole(StatusEnum::ACTIVE->value),
                callback: fn (Builder $query) => $query->orWhere('privacy', PrivacyEnum::PROTECTED->value)
            )
            ->paginate(15, ['slug', 'name', 'posts']);

        return view('site.categories.index', compact('categories'));
    }

    /**
     * Карточка категории
     *
     * @param Category $category
     *
     * @return  Factory|View
     * @throws  AuthorizationException
     */
    public function show(Category $category): Factory|View
    {
        $this->authorize('view', $category);

        $category->load(
            [
                'posts' => function (Builder $query) {
                    $query->select(['slug', 'title', 'author_id', 'published_at'])
                        ->whereNull('privacy')
                        ->when(
                            value: auth()->user()?->hasRole(StatusEnum::ACTIVE->value),
                            callback: fn (Builder $query) => $query->orWhere('privacy', PrivacyEnum::PROTECTED->value)
                        );
                },
                'posts.author:id,name',
            ]);

        return view('site.categories.show', compact('category'));
    }
}
