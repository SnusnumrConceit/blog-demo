<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\Post\PostCollection;
use App\Http\Resources\Api\v1\Post\PostResource;
use App\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Responsable;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Responsable
    {
        $posts = Post::with(['author:id,name'])
            ->public()
            ->simplePaginate(15, ['slug', 'title', 'content', 'published_at', 'author_id']);

        return new PostCollection($posts);
    }

    /**
     * Информация о публичном посте с категориями
     *
     * @param Post $post
     *
     * @return Responsable
     * @throws AuthorizationException
     */
    public function show(Post $post): Responsable
    {
        $this->authorize('view', $post);

        $post->load(['author:id,name']);

        return new PostResource($post);
    }
}
