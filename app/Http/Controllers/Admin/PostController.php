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
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Post::class);
    }

    /**
     * Список постов
     */
    public function index(): JsonResponse
    {
        $posts = Post::query()
            ->select(['id', 'name', 'privacy', 'published_at', 'created_at', 'updated_at']);

        return response()->json([
            'posts' => $posts->paginate()
        ]);
    }

    /**
     * Форма создания поста
     */
    public function create(): JsonResponse
    {
        $categories = auth()->user()->hasRole(StatusEnum::ADMIN)
            ? Category::all()
            : Category::whereNull('privacy')->orWhere('privacy', PrivacyEnum::PROTECTED)->get();

        return response()->json([
            'privacyItems' => PrivacyEnum::getValues(),
            'categories' => $categories->pluck('name', 'id')->all(),
        ]);
    }

    /**
     * Создание поста
     */
    public function store(StorePostRequest $request): JsonResponse
    {
       $post = Post::create([
            ...$request->validated(),
            'author_id' => auth()->user()->hasRole('admin') ? null : auth()->id(),
        ]);

       $post->categories()->sync($request->categories);

       event(new PostCreated(post: $post, privacy: $request->privacy));

        return response()->json(
            data: ['post' => $post],
            status: JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Карточка поста
     */
    public function show(Post $post): JsonResponse
    {
        $post->load('categories:id,name');

        return response()->json(['post' => $post]);
    }

    /**
     * Форма редактирования поста
     */
    public function edit(Post $post): JsonResponse
    {
        $post->load('categories:id,name');

        $categories = auth()->user()->hasRole(StatusEnum::ADMIN)
            ? Category::all()
            : Category::whereNull('privacy')->orWhere('privacy', PrivacyEnum::PROTECTED)->get();

        return response()->json([
            'privacyItems' => PrivacyEnum::getValues(),
            'categories' => $categories->merge($post->categories)->pluck('name', 'id')->all(),
            'post' => $post,
        ]);
    }

    /**
     * Обновление поста
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $post->update($request->validated());

        $post->categories()->sync($request->categories);

        event(new PostUpdated($post));

        return response()->json(data: [], status: JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Удаление поста
     */
    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        event(new PostDeleted($post));

        return response()->json(data: [], status: JsonResponse::HTTP_NO_CONTENT);
    }
}
