<?php

namespace Tests\Feature\Api\Category;

use App\Models\Category;
use App\Models\Post;
use Database\Factories\CategoryFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('cannot show category without bearer token', function () {
    /** @var Category $category */
    $category = Category::factory()->public()->create();

    /** @var TestCase $this */
    $response = $this->getJson(route('api.v1.categories.show', ['category' => $category->slug]));

    $this->assertInstanceOf(HttpException::class, $response->exception);
    $this->assertEquals('Вы не авторизованы', $response->exception->getMessage());
    $response->assertStatus(JsonResponse::HTTP_UNAUTHORIZED);
});

it('cannot show protected or private category', function () {
    /** @var Category $category */
    $category = Category::factory()->when(
        value: fake()->boolean,
        callback: fn (CategoryFactory $factory) => $factory->protected(),
        default: fn (CategoryFactory $factory) => $factory->private(),
    )->create();

    /** @var TestCase $this */
    $response = $this->withToken(config('auth.tokens.bearer.public'))
        ->getJson(route('api.v1.categories.show', ['category' => $category->slug]));

    $this->assertInstanceOf(AuthorizationException::class, $response->exception);
    $response->assertJson(['message' => '']);
    $response->assertStatus(JsonResponse::HTTP_NOT_FOUND);
});

it('cannot category be found by id', function () {
    $category = Category::factory()->public()->create();

    /** @var TestCase $this */
    $response = $this->withToken(config('auth.tokens.bearer.public'))
        ->getJson(route('api.v1.categories.show', ['category' => $category->id]));

    $this->assertInstanceOf(ModelNotFoundException::class, $response->exception);
    $response->assertJson(['message' => '']);
    $response->assertStatus(JsonResponse::HTTP_NOT_FOUND);
});

it('can get public category', function () {
    /** @var Category $category */
    $category = Category::factory()->public()->create();
    $posts = Post::factory()->public()->count(5)->create();

    $category->posts()->sync($posts->pluck('id')->all());
    $category->load('publicPosts:id,slug,title,published_at');

    /** @var TestCase $this */
    $response = $this->withToken(config('auth.tokens.bearer.public'))
        ->getJson(route('api.v1.categories.show', ['category' => $category->slug]));

    $response->assertSuccessful();

    $response->assertJson([
        'name' => $category->name,
        'slug' => $category->slug,
        'posts' => $category->publicPosts->only(['slug', 'title', 'published_at'])->all()
    ]);
});

it('can get public category without posts', function () {
    /** @var Category $category */
    $category = Category::factory()->public()->create();
    $category->load('publicPosts:id,slug,title,published_at');

    /** @var TestCase $this */
    $response = $this->withToken(config('auth.tokens.bearer.public'))
        ->getJson(route('api.v1.categories.show', ['category' => $category->slug]));

    $response->assertSuccessful();

    $response->assertJson([
        'name' => $category->name,
        'slug' => $category->slug,
        'posts' => []
    ]);
});
