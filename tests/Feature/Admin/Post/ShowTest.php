<?php

namespace Tests\Feature\Admin\Post;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

it('cannot show post', function () {
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
        /** @var TestCase $this */
        $response = is_null($user)
            ? $this->get(route('admin.posts.show', ['post' => $post->id]), $payload)
            : $this->actingAs($user)->get(route('admin.posts.show', ['post' => $post->id]), $payload);

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('can admin show post', function () {
    /** @var User $user */
    $user = User::factory()->admin()->create();

    /** @var Post $post */
    $post = Post::factory()->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->fromRoute('admin.posts.index')
        ->get(route('admin.posts.show', ['post' => $post->id]));

    $response->assertSuccessful();
    $response->assertViewIs('admin.posts.show');
    $response->assertViewHasAll([
        'post' => $post,
    ]);
});

it('can author show post', function () {
    /** @var User $user */
    $user = User::factory()->active()->create();

    /** @var Post $post */
    $post = Post::factory()->authoredBy($user->id)->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->fromRoute('admin.posts.index')
        ->get(route('admin.posts.show', ['post' => $post->id]));

    $response->assertSuccessful();
    $response->assertViewIs('admin.posts.show');
    $response->assertViewHasAll([
        'post' => $post,
    ]);
});

it('cannot active user show another author post', function () {
    /** @var User $user */
    $user = User::factory()->active()->create();

    /** @var Post $post */
    $post = Post::factory()->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->get(route('admin.posts.show', ['post' => $post->id]));

    $this->assertInstanceOf(AuthorizationException::class, $response->exception);
    $this->assertEquals('This action is unauthorized.', $response->exception->getMessage());
});
