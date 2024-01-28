<?php

namespace Tests\Feature\Admin\Post;

use App\Enums\Post\PrivacyEnum;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

it('cannot edit post', function () {
    /** @var Post $post */
    $post = Post::factory()->create();
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
            ? $this->get(route('admin.posts.edit', ['post' => $post->id]), $payload)
            : $this->actingAs($user)->get(route('admin.posts.edit', ['post' => $post->id]), $payload);

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('can admin edit post', function () {
    $user = User::factory()->admin()->create();

    /** @var Post $post */
    $post = Post::factory()->create();

    /** @var TestResponse $response */
    $response = $this->actingAs($user)->get(route('admin.posts.edit', ['post' => $post->id]));

    $response->assertSuccessful();
    $response->assertContent(json_encode([
        'privacyItems' => PrivacyEnum::getValues(),
        'post' => $post->toArray(),
    ]));
});

it('can author edit post', function () {
    $user = User::factory()->active()->create();

    /** @var Post $post */
    $post = Post::factory()->authoredBy($user->id)->create();

    /** @var TestResponse $response */
    $response = $this->actingAs($user)->get(route('admin.posts.edit', ['post' => $post->id]));

    $response->assertSuccessful();
    $response->assertContent(json_encode([
        'privacyItems' => PrivacyEnum::getValues(),
        'post' => $post->toArray(),
    ]));
});

it('cannot active user edit another author post', function () {
    $user = User::factory()->active()->create();

    /** @var Post $post */
    $post = Post::factory()->create();

    /** @var TestResponse $response */
    $response = $this->actingAs($user)->get(route('admin.posts.edit', ['post' => $post->id]));

    $this->assertInstanceOf(AuthorizationException::class, $response->exception);
    $this->assertEquals('This action is unauthorized.', $response->exception->getMessage());
});
