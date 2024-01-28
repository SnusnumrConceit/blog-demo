<?php

use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
    $user = User::factory()->active()->create();

    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['slug']);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->post(route('admin.posts.store'), $payload);

    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseHas('posts', [
        'title' => $payload['title'],
        'slug' => Str::slug(title: $payload['title'], language: 'ru'),
        'content' => $payload['content'],
        'privacy' => $payload['privacy'],
        'published_at' => null,
        'author_id' => $user->id,
    ]);
});

it('can admin user store post', function () {
    $user = User::factory()->admin()->create();

    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['slug']);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->post(route('admin.posts.store'), $payload);

    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseHas('posts', [
        'title' => $payload['title'],
        'slug' => Str::slug(title: $payload['title'], language: 'ru'),
        'content' => $payload['content'],
        'privacy' => $payload['privacy'],
        'published_at' => null,
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
        'published_at' => [Str::random(), [], fake()->randomDigit(), Carbon::yesterday()->format('Y-m-d H:i:s')]
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
    $user = User::factory()->active()->create();
    /** @var Post $similarPost */
    $similarPost = Post::factory()->create();

    $payload = Post::factory()->simple()->make(['title' => mb_strtoupper($similarPost->title)])->toArray();
    Arr::forget($payload, ['slug', 'author_id']);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->post(route('admin.posts.store'), $payload);

    $this->assertInstanceOf(ValidationException::class, $response->exception);
    $response->assertStatus(Response::HTTP_FOUND);
});
