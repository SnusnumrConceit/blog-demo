<?php

namespace App\Http\Controllers\Site;

use App\Enums\PrivacyEnum;
use App\Enums\User\StatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class PostController extends Controller
{
    /**
     * Список доступных постов
     *
     * @return Factory|View
     */
    public function index(): Factory|View
    {
        $posts = Post::with('author:id,name')
            ->whereNull('privacy')
            ->when(
                value: auth()->user()?->hasRole(StatusEnum::ACTIVE->value),
                callback: fn (Builder $query) => $query->orWhere('privacy', PrivacyEnum::PROTECTED->value)
            )->paginate(15, ['slug', 'title', 'author_id', 'published_at']);

        return view('site.posts.index', compact('posts'));
    }

    /**
     * Карточка поста
     *
     * @param Post $post
     *
     * @return Factory|View
     * @throws AuthorizationException
     */
    public function show(Post $post): Factory|View
    {
        $this->authorize('sitePostShow', $post);

        PostService::incrementView(post: $post, user: auth()->user());

        return view('site.posts.show', compact('post'));
    }
}
