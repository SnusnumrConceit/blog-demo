<?php

use App\Enums\Post\PrivacyEnum;
use App\Jobs\Admin\Post\PublishPost;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Database\Factories\CategoryFactory;
use Database\Factories\UserFactory;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

beforeEach(function () {
    Carbon::setTestNow(now());
});

it('cannot store post', function () {
    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['slug', 'author_id']);

    $users = [
        null,
        User::factory()->blocked()->create(),
        User::factory()->unverified()->create()
    ];

    foreach ($users as $user) {
        /** @var TestResponse $response */
        $response = is_null($user)
            ? $this->post(route('admin.posts.store'), $payload)
            : $this->actingAs($user)->post(route('admin.posts.store'), $payload);

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('can active user store post', function () {
    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->public()
        ->count(3)
        ->create()
        ->pluck('id')
        ->all();

    $user = User::factory()->active()->create();

    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['slug']);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->post(
            route('admin.posts.store'),
            $payload + ['categories' => $categoriesIds]
        );

    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseHas('posts', [
        'title' => $payload['title'],
        'slug' => Str::slug(title: $payload['title'], language: 'ru'),
        'content' => $payload['content'],
        'privacy' => $payload['privacy'],
        'published_at' => Carbon::getTestNow(),
        'author_id' => $user->id,
    ]);

    /** @var Post $post */
    $post = Post::where('title', $payload['title'])->first();
    $post->load('categories:id');

    $this->assertCount(count($categoriesIds), $post->categories->pluck('id')->all());
});

it('can admin user store post', function () {
    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->count(3)
        ->create([
            'privacy' => PrivacyEnum::getRandomValue(),
        ])
        ->pluck('id')
        ->all();

    $user = User::factory()->admin()->create();

    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['slug']);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->post(
            route('admin.posts.store'),
            $payload + ['categories' => $categoriesIds]
        );

    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseHas('posts', [
        'title' => $payload['title'],
        'slug' => Str::slug(title: $payload['title'], language: 'ru'),
        'content' => $payload['content'],
        'privacy' => $payload['privacy'],
        'published_at' => Carbon::getTestNow(),
        'author_id' => null,
    ]);
});

it('can not store category with invalid params', function () {
    $user = User::factory()->active()->create();

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
            /** @var TestResponse $response */
            $response = $this->actingAs($user)
                ->post(route('admin.posts.store'), array_merge($payload, [$param => $value]));

            $this->assertInstanceOf(ValidationException::class, $response->exception);
            $response->assertStatus(Response::HTTP_FOUND);
        }
    }
});

it('cannot store post with duplicated slug post', function () {
    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->protected()
        ->count(3)
        ->create()
        ->pluck('id')
        ->all();

    $user = User::factory()->active()->create();
    /** @var Post $similarPost */
    $similarPost = Post::factory()->create();

    $payload = Post::factory()->simple()->make(['title' => mb_strtoupper($similarPost->title)])->toArray();
    Arr::forget($payload, ['slug', 'author_id']);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->post(
            route('admin.posts.store'),
            $payload + ['categories' => $categoriesIds]
        );

    $this->assertInstanceOf(ValidationException::class, $response->exception);
    $response->assertStatus(Response::HTTP_FOUND);
});

it('can store category with delayed published_at date', function () {
    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->public()
        ->count(3)
        ->create()
        ->pluck('id')
        ->all();

    $user = User::factory()->active()->create();

    $payload = Post::factory()->planned(Carbon::getTestNow()->addHour())->make()->toArray();
    Arr::forget($payload, ['slug']);

    Queue::fake();

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->post(
            route('admin.posts.store'),
            $payload + ['categories' => $categoriesIds]
        );

    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseHas('posts', [
        'title' => $payload['title'],
        'slug' => Str::slug(title: $payload['title'], language: 'ru'),
        'content' => $payload['content'],
        'privacy' => PrivacyEnum::PRIVATE,
        'published_at' => $payload['published_at'],
        'author_id' => $user->id,
    ]);

    /** @var Post $post */
    $post = Post::where('slug', Str::slug(title: $payload['title'], language: 'ru'))->first();
    $post->load('categories:id');

    $this->assertCount(count($categoriesIds), $post->categories->pluck('id')->all());

    Queue::assertPushed(fn (PublishPost $job) =>
        $job->postId === $post->id
        && $job->privacy === $payload['privacy']
        && $job->delay === Carbon::parse($post->published_at)->diffInSeconds(Carbon::getTestNow())
    );
});

it('Cannot active user store post with protected or private categories', function () {
    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->when(
            value: fake()->boolean,
            callback: fn (CategoryFactory $factory) => $factory->protected(),
            default: fn (CategoryFactory $factory) => $factory->private()
        )
        ->count(3)
        ->create()
        ->pluck('id')
        ->all();

    $user = User::factory()->active()->create();

    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['slug']);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->post(
            route('admin.posts.store'),
            $payload + ['categories' => $categoriesIds]
        );

    $this->assertInstanceOf(ValidationException::class, $response->exception);
    $response->assertStatus(Response::HTTP_FOUND);
});

it('Can store post with excess categories', function () {
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

    $payload = Post::factory()->planned(Carbon::getTestNow()->addHour())->make()->toArray();
    Arr::forget($payload, ['slug']);

    Queue::fake();

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->post(
            route('admin.posts.store'),
            $payload + ['categories' => Arr::shuffle($categoriesIds + $invalidCategoriesIds)],
        );

    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseHas('posts', [
        'title' => $payload['title'],
        'slug' => Str::slug(title: $payload['title'], language: 'ru'),
        'content' => $payload['content'],
        'privacy' => PrivacyEnum::PRIVATE,
        'published_at' => $payload['published_at'],
        'author_id' => $user->isAdmin() ? null : $user->id,
    ]);

    /** @var Post $post */
    $post = Post::where('slug', Str::slug(title: $payload['title'], language: 'ru'))->first();
    $post->load('categories:id');

    $this->assertCount(count($categoriesIds), $post->categories->pluck('id')->all());
});
