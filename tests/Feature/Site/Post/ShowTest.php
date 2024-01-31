<?php

namespace Tests\Feature\Site\Post;

use App\Enums\Post\PrivacyEnum;
use App\Models\Post;
use App\Models\PostView;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
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

it('can guest view public post', function () {
    /** @var Post $post */
    $post = Post::factory()->public()->create();

    $locale = 'ru';
    /** @var TestResponse $response */
    $response = $this->withHeaders(['Accept-Language' => $locale])
        ->get(route('site.posts.show', ['post' => $post->slug]));

    $response->assertSuccessful();

    $response->assertViewIs('site.posts.show');
    $response->assertViewHas([
        'post' => $post
    ]);

    /** проверяем, что счётчик просмотров не изменился */
    $this->assertFalse($post->views()->exists());
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

    $response->assertViewIs('site.posts.show');
    $response->assertViewHas([
        'post' => $post
    ]);
    /** проверяем, что счётчик просмотров не изменился */
    $this->assertFalse($post->views()->exists());
});

it('can active user view protected or public post', function () {
    $user = User::factory()->active()->create();
    /** @var Post $post */
    $post = Post::factory()->create([
        'privacy' => Arr::random([null, PrivacyEnum::PROTECTED])
    ]);

    $locale = 'ru';
    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->withHeaders(['Accept-Language' => $locale])
        ->get(route('site.posts.show', ['post' => $post->slug]));

    $response->assertSuccessful();

    $response->assertViewIs('site.posts.show');
    $response->assertViewHas([
        'post' => $post
    ]);

    /** проверяем счётчик просмотров */
    $this->assertEquals(1, $post->views()->count());
});

it('cannot increment view when post has been viewed earlier', function () {
    $user = User::factory()->active()->create();
    /** @var Post $post */
    $post = Post::factory()->create([
        'privacy' => Arr::random([null, PrivacyEnum::PROTECTED])
    ]);
    PostView::factory()
        ->user($user->id)
        ->post($post->id)
        ->create();

    $locale = 'ru';
    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->withHeaders(['Accept-Language' => $locale])
        ->get(route('site.posts.show', ['post' => $post->slug]));

    $response->assertSuccessful();

    $response->assertViewIs('site.posts.show');
    $response->assertViewHas([
        'post' => $post
    ]);

    /** проверяем, что счётчик просмотров не изменился */
    $this->assertEquals(1, $post->views()->count());
});
