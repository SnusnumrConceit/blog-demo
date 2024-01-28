<?php

namespace Tests\Feature\Site\Post;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('can view post only in Russia', function () {
    /** @var Post $post */
    $post = Post::factory()->create();

    $locale = fake()->locale();
    while ($locale === 'ru_RU') {
        $locale = fake()->locale();
    }

    /** @var TestResponse $response */
    $response = $this->withHeaders(['Accept-Language' => $locale])
        ->get(route('site.posts.show', ['post' => $post->slug]));

    $this->assertNotNull($response->exception);
    $this->assertInstanceOf(HttpException::class, $response->exception);
    $this->assertEquals('Sorry, but your locale is not compatible.', $response->exception->getMessage());
    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
});

it('cannot guest view protected post', function () {
    /** @var Post $post */
    $post = Post::factory()->protected()->create();

    $locale = 'ru';
    /** @var TestResponse $response */
    $response = $this->withHeaders(['Accept-Language' => $locale])
        ->get(route('site.posts.show', ['post' => $post->slug]));

    $this->assertNotNull($response->exception);
    $this->assertInstanceOf(AuthorizationException::class, $response->exception);
});

it('cannot not admin view private post', function () {
    /** @var Post $post */
    $post = Post::factory()->private()->create();
    $locale = 'ru';
    $users = [
        null,
        User::factory()->active()->create(),
        User::factory()->blocked()->create(),
        User::factory()->unverified()->create()
    ];

    foreach ($users as $user) {
        /** @var TestResponse $response */
        $request = $user ? $this->actingAs($user) : $this;
        $response = $request
            ->withHeaders(['Accept-Language' => $locale])
            ->get(route('site.posts.show', ['post' => $post->slug]));

        $this->assertNotNull($response->exception);
        $this->assertInstanceOf(AuthorizationException::class, $response->exception);
    }
});

it('can admin view private post', function () {
    $user = User::factory()->admin()->create();
    /** @var Post $post */
    $post = Post::factory()->private()->create();

    $locale = 'ru';
    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->withHeaders(['Accept-Language' => $locale])
        ->get(route('site.posts.show', ['post' => $post->slug]));

    $response->assertSuccessful();
});
