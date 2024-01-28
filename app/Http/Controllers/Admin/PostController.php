<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Post\PrivacyEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Post\StorePostRequest;
use App\Http\Requests\Admin\Post\UpdatePostRequest;
use App\Jobs\Admin\Post\PublishPost;
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
        return response()->json([
            'privacyItems' => PrivacyEnum::getValues(),
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

       if ($post->published_at) {
           dispatch(new PublishPost(postId: $post->id, privacy: $request->privacy))
               ->delay(now()->diffInSeconds($post->published_at));
       }

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
        return response()->json(['post' => $post]);
    }

    /**
     * Форма редактирования поста
     */
    public function edit(Post $post): JsonResponse
    {
        return response()->json([
            'privacyItems' => PrivacyEnum::getValues(),
            'post' => $post,
        ]);
    }

    /**
     * Обновление поста
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $post->update($request->validated());

        return response()->json(data: [], status: JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Удаление поста
     */
    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return response()->json(data: [], status: JsonResponse::HTTP_NO_CONTENT);
    }
}
