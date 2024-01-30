<?php

namespace Tests\Feature\Api\Post;

use App\Models\Category;
use App\Models\Post;
use Database\Factories\PostFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

it('cannot show protected or private post', function () {
    /** @var Post $post */
    $post = Post::factory()->when(
        value: fake()->boolean,
        callback: fn (PostFactory $factory) => $factory->protected(),
        default: fn (PostFactory $factory) => $factory->private(),
    )->create();

    /** @var TestResponse $response */
    $response = $this->getJson(route('api.v1.posts.show', ['post' => $post->slug]));

    $this->assertInstanceOf(AuthorizationException::class, $response->exception);
    $response->assertJson(['message' => '']);
    $response->assertStatus(JsonResponse::HTTP_NOT_FOUND);
});

it('cannot post be found by id', function () {
    $post = Post::factory()->public()->create();

    /** @var TestResponse $response */
    $response = $this->getJson(route('api.v1.posts.show', ['post' => $post->id]));

    $this->assertInstanceOf(ModelNotFoundException::class, $response->exception);
    $response->assertJson(['message' => '']);
    $response->assertStatus(JsonResponse::HTTP_NOT_FOUND);
});

it('can get public post', function () {
    /** @var Post $post */
    $post = Post::factory()->public()->create();

    /** @var TestResponse $response */
    $response = $this->getJson(route('api.v1.posts.show', ['post' => $post->slug]));

    $response->assertSuccessful();

    $response->assertJson(
        value: [
            'slug' => $post->slug,
            'title' => $post->title,
            'content' => $post->censored_content,
            'published_at' => $post->published_at->format('Y-m-d H:i:s'),
            'author' => [
                'name' => $post->author->name,
            ]
        ],
        strict: true
    );
});
