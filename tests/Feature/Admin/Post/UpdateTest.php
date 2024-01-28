<?php

namespace Tests\Feature\Admin\Post;

use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
        /** @var TestResponse $response */
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

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->put(route('admin.posts.update', ['post' => $post->id]), $payload);

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseHas('posts', [
        'title' => $payload['title'],
        'slug' => Str::slug(title: $payload['title'], language: 'ru'),
        'content' => $payload['content'],
        'privacy' => $payload['privacy'],
        'published_at' => null,
        'author_id' => $post->author_id,
    ]);
});

it('can author update post', function () {
    $user = User::factory()->active()->create();
    /** @var Post $post */
    $post = Post::factory()->authoredBy($user->id)->create();

    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['slug', 'author_id']);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->put(route('admin.posts.update', ['post' => $post->id]), $payload);

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseHas('posts', [
        'title' => $payload['title'],
        'slug' => Str::slug(title: $payload['title'], language: 'ru'),
        'content' => $payload['content'],
        'privacy' => $payload['privacy'],
        'published_at' => null,
        'author_id' => $user->id,
    ]);
});

it('cannot active user update another author post', function () {
    $user = User::factory()->active()->create();
    /** @var Post $post */
    $post = Post::factory()->create();

    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['slug', 'author_id']);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->put(route('admin.posts.update', ['post' => $post->id]), $payload);

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
        'published_at' => [Str::random(), [], fake()->randomDigit(), Carbon::yesterday()->format('Y-m-d H:i:s')]
    ];

    foreach ($invalidParams as $param => $values) {
        foreach ($values as $value) {
            /** @var TestResponse $response */
            $response = $this->actingAs($user)
                ->put(route('admin.posts.update', ['post' => $post->id]), array_merge($payload, [$param => $value]));

            try {
                $this->assertInstanceOf(ValidationException::class, $response->exception);
            } catch (\Throwable $exception) {
                dump([$param => $value]);
            }
            $response->assertStatus(Response::HTTP_FOUND);
        }
    }
});

it('cannot update post with duplicate title', function () {
    $user = User::factory()->active()->create();
    $post = Post::factory()->authoredBy($user->id)->create();
    /** @var Post $similarPost */
    $similarPost = Post::factory()->create();

    $payload = Post::factory()->simple()->make(['title' => mb_strtoupper($similarPost->title)])->toArray();
    Arr::forget($payload, ['slug', 'author_id']);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->put(route('admin.posts.update', ['post' => $post->id]), $payload);

    $this->assertInstanceOf(ValidationException::class, $response->exception);
    $response->assertStatus(Response::HTTP_FOUND);
});
