<?php

namespace Tests\Feature\Api\v1\Post;

use App\Models\Post;
use Illuminate\Foundation\Testing\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('cannot show post without bearer token', function () {
    /** @var TestCase $this */
    $response = $this->getJson(route('api.v1.posts.index'));

    $this->assertInstanceOf(HttpException::class, $response->exception);
    $this->assertEquals('Вы не авторизованы', $response->exception->getMessage());
    $response->assertStatus(JsonResponse::HTTP_UNAUTHORIZED);
});

it('Can index posts', function () {
    $posts = Post::factory()->count(20)->create();
    $publicCategoriesCount = $posts->whereNull('privacy')->count();
    $perPage = 15;

    /** @var TestCase $this */
    $response = $this->withToken(config('auth.tokens.bearer.public'))
        ->getJson(route('api.v1.posts.index'));

    $response->assertJson(
        value: [
            'posts' => $posts->whereNull('privacy')->only(['slug', 'title', 'censored_content', 'published_at', 'author'])->all(),
            'links' => [
                'first' => route('api.v1.posts.index', ['page' => 1]),
                "last" => null,
                "prev" => null,
                "next" => $publicCategoriesCount >= 15 ? route('api.v1.posts.index', ['page' => 1]) : null,
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'path' => route('api.v1.posts.index'),
                'per_page' => $perPage,
                'to' => min($publicCategoriesCount, $perPage),
            ],
        ],
        strict: true
    );
});

it('Can index posts from page#2', function () {
    $posts = Post::factory()->public()->count(20)->create();
    $perPage = 15;

    /** @var TestCase $this */
    $response = $this->withToken(config('auth.tokens.bearer.public'))
        ->getJson(route('api.v1.posts.index', ['page' => 2]));

    $response->assertJson(
        value: [
            'posts' => $posts->whereNull('privacy')->only(['slug', 'title', 'published_at', 'censored_content', 'author'])->all(),
            'links' => [
                'first' => route('api.v1.posts.index', ['page' => 1]),
                "last" => null,
                "prev" => route('api.v1.posts.index', ['page' => 1]),
                "next" => null,
            ],
            'meta' => [
                'current_page' => 2,
                'from' => 16,
                'path' => route('api.v1.posts.index'),
                'per_page' => $perPage,
                'to' => $posts->count(),
            ],
        ],
        strict: true
    );
});
