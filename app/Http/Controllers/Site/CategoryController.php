<?php

namespace App\Http\Controllers\Site;

use App\Enums\Category\PrivacyEnum;
use App\Enums\Post\PrivacyEnum as PostPrivacyEnum;
use App\Enums\User\StatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Список категорий
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $categories = Category::with(['posts' => function (Builder $query) {
            $query->select(['slug', 'title'])
                ->whereNull('privacy')
                ->when(
                    value: auth()->user()?->hasRole(StatusEnum::ACTIVE),
                    callback: fn (Builder $query) => $query->orWhere('privacy', PostPrivacyEnum::PROTECTED)
                );
            }])
            ->whereNull('privacy')
            ->when(
                value: auth()->user()?->hasRole(StatusEnum::ACTIVE),
                callback: fn (Builder $query) => $query->orWhere('privacy', PrivacyEnum::PROTECTED)
            )->paginate(15, ['slug', 'name']);

        return response()->json([
            'categories' => $categories
        ]);
    }

    /**
     * Карточка категории
     *
     * @param Category $category
     *
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        $category->load(
            [
                'posts' => function (Builder $query) {
                    $query->select(['slug', 'title', 'author_id'])
                        ->whereNull('privacy')
                        ->when(
                            value: auth()->user()?->hasRole(StatusEnum::ACTIVE),
                            callback: fn (Builder $query) => $query->orWhere('privacy', PostPrivacyEnum::PROTECTED)
                        );
                },
                'posts.author:id,name',
            ]);

        return response()->json([
            'category' => $category->only(['slug', 'name', 'posts']),
        ]);
    }
}
