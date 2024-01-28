<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
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