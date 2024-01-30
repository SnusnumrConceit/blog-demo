<?php

namespace Tests\Feature\Admin\Post;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Database\Factories\CategoryFactory;
use Database\Factories\PostFactory;
use Database\Factories\UserFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

beforeEach(function () {
    Carbon::setTestNow(now());
});

it('cannot update post', function () {
    /** @var Post $post */
    $post = Post::factory()->create();
    $payload = Post::factory()->make()->toArray();
    Arr::forget($payload, ['slug']);

    $users = [
        null,
        User::factory()->blocked()->create(),
        User::factory()->unverified()->create()
    ];

    foreach ($users as $user) {
        /** @var TestCase $this */
        $response = is_null($user)
            ? $this->put(route('admin.posts.update', ['post' => $post->id]), $payload)
            : $this->actingAs($user)
                ->put(route('admin.posts.update', ['post' => $post->id]), $payload);

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('can admin update post', function () {
    $user = User::factory()->admin()->create();
    /** @var Post $post */
    $post = Post::factory()->create();

    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['slug', 'author_id']);

    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->count(3)
        ->create()
        ->pluck('id')
        ->all();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->put(
            route('admin.posts.update', ['post' => $post->id]),
            $payload + ['categories' => $categoriesIds]
        );

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseHas('posts', [
        'title' => $payload['title'],
        'slug' => Str::slug(title: $payload['title'], language: 'ru'),
        'content' => $payload['content'],
        'privacy' => $payload['privacy'],
        'published_at' => $post->published_at->format('Y-m-d H:i:s'),
        'author_id' => $post->author_id,
    ]);

    $this->assertEquals(count($categoriesIds), $post->categories->count());
});

it('can author update post', function () {
    /** @var User $user */
    $user = User::factory()->active()->create();
    /** @var Post $post */
    $post = Post::factory()->authoredBy($user->id)->create();

    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['slug', 'author_id']);

    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->when(
            value: fake()->boolean,
            callback: fn (CategoryFactory $factory) => $factory->protected(),
            default: fn (CategoryFactory $factory) => $factory->public(),
        )
        ->count(3)
        ->create()
        ->pluck('id')
        ->all();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->put(
            route('admin.posts.update', ['post' => $post->id]),
            $payload + ['categories' => $categoriesIds]
        );

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseHas('posts', [
        'title' => $payload['title'],
        'slug' => Str::slug(title: $payload['title'], language: 'ru'),
        'content' => $payload['content'],
        'privacy' => $payload['privacy'],
        'published_at' => $post->published_at->format('Y-m-d H:i:s'),
        'author_id' => $user->id,
    ]);

    $this->assertEquals(count($categoriesIds), $post->categories->count());
});

it('cannot active user update another author post', function () {
    $user = User::factory()->active()->create();
    /** @var Post $post */
    $post = Post::factory()->create();

    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['slug', 'author_id']);

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->put(
            route('admin.posts.update', ['post' => $post->id]),
            $payload
        );

    $this->assertInstanceOf(AuthorizationException::class, $response->exception);
    $this->assertEquals('This action is unauthorized.', $response->exception->getMessage());
});

it('cannot update post with invalid params', function () {
    $user = User::factory()->admin()->create();
    /** @var Post $post */
    $post = Post::factory()->authoredBy($user->id)->create();

    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['author_id', 'slug']);

    $invalidParams = [
        'title' => [null, [], Str::random(101),],
        'content' => [null, [], Str::random(65636),],
        'privacy' => [Str::random(), [], fake()->randomDigit()],
        'published_at' => [Str::random(), [], fake()->randomDigit(), Carbon::yesterday()->format('Y-m-d H:i:s')],
        'categories' => [null, [], [range(-10, 0)], Str::random(), rand(-10, 10), [1, 2, 3]],
    ];

    foreach ($invalidParams as $param => $values) {
        foreach ($values as $value) {
            /** @var TestCase $this */
            $response = $this->actingAs($user)
                ->put(route('admin.posts.update', ['post' => $post->id]), array_merge($payload, [$param => $value]));

            $this->assertInstanceOf(ValidationException::class, $response->exception);
            $response->assertStatus(Response::HTTP_FOUND);
        }
    }
});

it('cannot update post with duplicate title', function () {
    /** @var User $user */
    $user = User::factory()->active()->create();
    /** @var Post $post */
    $post = Post::factory()->authoredBy($user->id)->create();
    /** @var Post $similarPost */
    $similarPost = Post::factory()->authoredBy($user->id)->create();

    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->protected()
        ->count(3)
        ->create()
        ->pluck('id')
        ->all();

    $payload = Post::factory()->simple()->make(['title' => mb_strtoupper($similarPost->title)])->toArray();
    Arr::forget($payload, ['slug', 'author_id']);

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->put(
            route('admin.posts.update', ['post' => $post->id]),
            $payload + $categoriesIds
        );

    $this->assertInstanceOf(ValidationException::class, $response->exception);
    $response->assertStatus(Response::HTTP_FOUND);
});

it('Cannot active user update post with private categories', function () {
    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->private()
        ->count(3)
        ->create()
        ->pluck('id')
        ->all();

    $user = User::factory()->active()->create();

    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['slug']);

    /** @var Post $post */
    $post = Post::factory()->authoredBy($user->id)->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->put(
            route('admin.posts.update', ['post' => $post->id]),
            $payload + ['categories' => $categoriesIds]
        );

    $this->assertInstanceOf(ValidationException::class, $response->exception);
    $response->assertStatus(Response::HTTP_FOUND);
});

it('Can update post with excess categories', function () {
    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->public()
        ->count(3)
        ->create()
        ->pluck('id')
        ->all();

    $invalidCategoriesIds = [
        null,
        ...range(-3, 0),
        Str::random(),
        [],
        ...$categoriesIds,
        ...$categoriesIds
    ];

    /** @var User $user */
    $user = User::factory()->when(
        value: fake()->boolean,
        callback: fn (UserFactory $factory) => $factory->active(),
        default: fn (UserFactory $factory) => $factory->admin(),
    )->create();

    /** @var Post $post */
    $post = Post::factory()
        ->when(
            value: ! $user->isAdmin(),
            callback: fn (PostFactory $factory) => $factory->authoredBy($user->id)
        )->create();

    $payload = Post::factory()->planned(Carbon::getTestNow()->addHour())->make(['author_id' => null])->toArray();
    Arr::forget($payload, ['slug']);

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->put(
            route('admin.posts.update', ['post' => $post->id]),
            $payload + ['categories' => Arr::shuffle($categoriesIds + $invalidCategoriesIds)],
        );

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseHas('posts', [
        'title' => $payload['title'],
        'slug' => Str::slug(title: $payload['title'], language: 'ru'),
        'content' => $payload['content'],
        'privacy' => $payload['privacy'],
        'published_at' => $post->published_at->format('Y-m-d H:i:s'),
        'author_id' => $user->isAdmin() ? $post->author_id : $user->id,
    ]);

    $post->load('categories:id');

    $this->assertCount(count($categoriesIds), $post->categories->pluck('id')->all());
});
