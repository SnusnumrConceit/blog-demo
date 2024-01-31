<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Post\PrivacyEnum;
use App\Enums\User\StatusEnum;
use App\Events\Post\PostCreated;
use App\Events\Post\PostDeleted;
use App\Events\Post\PostUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Post\StorePostRequest;
use App\Http\Requests\Admin\Post\UpdatePostRequest;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class PostController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Post::class);
    }

    /**
     * Список постов
     *
     * @return Factory|View
     */
    public function index(): Factory|View
    {
        $posts = Post::with('author:id,name')
            ->when(
                value: ! auth()->user()->isAdmin(),
                callback: fn (Builder $query) => $query->where('author_id', auth()->id())
            )->paginate(15, ['id', 'title', 'privacy', 'published_at', 'created_at', 'updated_at']);

        return view(view: 'admin.posts.index', data: compact('posts'));
    }

    /**
     * Форма создания поста
     *
     * @return Factory|View
     */
    public function create(): Factory|View
    {
        $categories = auth()->user()->hasRole(StatusEnum::ADMIN)
            ? Category::all()
            : Category::whereNull('privacy')->orWhere('privacy', PrivacyEnum::PROTECTED)->get();

        return view(view: 'admin.posts.create', data: [
            'privacyItems' => [null, ...PrivacyEnum::getValues()],
            'categories' => $categories->pluck('name', 'id')->all(),
        ]);
    }

    /**
     * Создание поста
     *
     * @param StorePostRequest $request
     *
     * @return RedirectResponse|Redirector
     */
    public function store(StorePostRequest $request): RedirectResponse|Redirector
    {
       $post = Post::create([
            ...$request->validated(),
            'author_id' => auth()->user()->hasRole('admin') ? null : auth()->id(),
        ]);

       $post->categories()->sync($request->categories);

       event(new PostCreated(post: $post, privacy: $request->privacy));

        return redirect(route('admin.posts.show', ['post' => $post->id]))
            ->with(key: 'success', value: 'Пост успешно создан');
    }

    /**
     * Карточка поста
     */
    public function show(Post $post): Factory|View
    {
        $post->load('categories:id,name');

        return view(view: 'admin.posts.show', data: compact('post'));
    }

    /**
     * Форма редактирования поста
     *
     * @param Post $post
     *
     * @return Factory|View
     */
    public function edit(Post $post): Factory|View
    {
        $post->load(['categories' => fn (BelongsToMany $query) =>
            $query->select(['categories.id as id', 'name', 'privacy'])
                ->whereNull('privacy')
                ->when(
                    value: ! auth()->user()->isAdmin(),
                    callback: fn () => $query->orWhere('privacy', PrivacyEnum::PROTECTED)
                )
        ]);

        $categories = auth()->user()->isAdmin()
            ? Category::all()
            : Category::whereNull('privacy')->orWhere('privacy', PrivacyEnum::PROTECTED)->get();

        return view(view: 'admin.posts.edit', data: [
            'privacyItems' => [null, ...PrivacyEnum::getValues()],
            'categories' => $categories->merge($post->categories)->pluck('name', 'id')->all(),
            'post' => $post,
        ]);
    }

    /**
     * Обновление поста
     *
     * @param UpdatePostRequest $request
     * @param Post $post
     *
     * @return RedirectResponse|Redirector
     */
    public function update(UpdatePostRequest $request, Post $post): RedirectResponse|Redirector
    {
        $post->update($request->validated());

        $post->categories()->sync($request->categories);

        event(new PostUpdated($post));

        return redirect(route('admin.posts.show', ['post' => $post->id]))
            ->with(key: 'success', value: 'Пост успешно обновлён');
    }

    /**
     * Удаление поста
     *
     * @param Post $post
     *
     * @return RedirectResponse|Redirector
     */
    public function destroy(Post $post): RedirectResponse|Redirector
    {
        $post->delete();

        event(new PostDeleted($post));

        return redirect(route('admin.posts.index'))
            ->with(key: 'success', value: 'Пост успешно удалён');
    }
}
