<?php

namespace Tests\Feature\Admin\Post;

use App\Enums\Post\PrivacyEnum;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

it('cannot delete post', function () {
    /** @var Post $post */
    $post = Post::factory()->create();

    $users = [
        null,
        User::factory()->blocked()->create(),
        User::factory()->unverified()->create()
    ];

    foreach ($users as $user) {
        /** @var TestResponse $response */
        $response = is_null($user)
            ? $this->delete(route('admin.posts.destroy', ['post' => $post->id]))
            : $this->actingAs($user)->delete(route('admin.posts.destroy', ['post' => $post->id]));

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('can admin delete post', function () {
    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->count(3)
        ->create(['privacy' => Arr::random([null, PrivacyEnum::getRandomValue()])])
        ->pluck('id')
        ->all();
    $user = User::factory()->admin()->create();

    /** @var Post $post */
    $post = Post::factory()->create();
    $post->categories()->sync($categoriesIds);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)->delete(route('admin.posts.destroy', ['post' => $post->id]));

    $response->assertSuccessful();
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $response->assertContent('');
    $this->assertDatabaseMissing('posts', [
        'title' => $post->title,
        'slug' => Str::slug(title: $post->title, language: 'ru'),
        'privacy' => $post->privacy,
        'author_id' => $post->author_id,
        'content' => $post->content,
    ]);

    $this->assertFalse(DB::table('categories_posts')->where('post_id', $post->id)->exists());
});

it('can author delete post', function () {
    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->public()
        ->count(3)
        ->create()
        ->pluck('id')
        ->all();

    $user = User::factory()->active()->create();

    /** @var Post $post */
    $post = Post::factory()->authoredBy($user->id)->create();
    $post->categories()->sync($categoriesIds);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)->delete(route('admin.posts.destroy', ['post' => $post->id]));

    $response->assertSuccessful();
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $response->assertContent('');
    $this->assertDatabaseMissing('posts', [
        'title' => $post->title,
        'slug' => Str::slug(title: $post->title, language: 'ru'),
        'privacy' => $post->privacy,
        'author_id' => $post->author_id,
        'content' => $post->content,
    ]);

    $this->assertFalse(DB::table('categories_posts')->where('post_id', $post->id)->exists());
});

it('cannot active user delete another author post', function () {
    $user = User::factory()->active()->create();

    /** @var Post $post */
    $post = Post::factory()->create();

    /** @var TestResponse $response */
    $response = $this->actingAs($user)->get(route('admin.posts.destroy', ['post' => $post->id]));

    $this->assertInstanceOf(AuthorizationException::class, $response->exception);
    $this->assertEquals('This action is unauthorized.', $response->exception->getMessage());
});
