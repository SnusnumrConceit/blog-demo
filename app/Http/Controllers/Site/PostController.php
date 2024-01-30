<?php

namespace App\Http\Controllers\Site;

use App\Enums\Post\PrivacyEnum;
use App\Enums\User\StatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    /**
     * Список доступных постов
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $posts = Post::with('author:id,name')
            ->whereNull('privacy')
            ->when(
                value: auth()->user()?->hasRole(StatusEnum::ACTIVE),
                callback: fn (Builder $query) => $query->orWhere('privacy', PrivacyEnum::PROTECTED)
            )->paginate(15, ['slug', 'title', 'author_id']);

        return response()->json([
            'posts' => $posts
        ]);
    }

    /**
     * Карточка поста
     *
     * @param Post $post
     *
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(Post $post): JsonResponse
    {
        $this->authorize('sitePostShow', $post);

        PostService::incrementView(post: $post, user: auth()->user());

        return response()->json([
            'post' => $post,
        ]);
    }
}
