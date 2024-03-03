<?php

namespace Tests\Feature\Admin\Post;

use App\Enums\PrivacyEnum;
use App\Mail\Post\PostDeleted;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Database\Factories\PostFactory;
use Database\Factories\UserFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

beforeEach(function () {
    Mail::fake();
});

it('cannot delete post', function () {
    /** @var Post $post */
    $post = Post::factory()->create();

    $users = [
        null,
        User::factory()->blocked()->create(),
        User::factory()->unverified()->create()
    ];

    foreach ($users as $user) {
        /** @var TestCase $this */
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

    /** @var User $user */
    $user = User::factory()->admin()->create();

    /** @var Post $post */
    $post = Post::factory()->create();
    $post->categories()->sync($categoriesIds);

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->fromRoute('admin.posts.show', ['post' => $post->id])
        ->delete(route('admin.posts.destroy', ['post' => $post->id]));

    $response->assertRedirectToRoute('admin.posts.index');
    $response->assertSessionHas(key: 'success', value: 'Пост успешно удалён');

    $this->assertDatabaseMissing('posts', [
        'title' => $post->title,
        'slug' => Str::slug(title: $post->title, language: 'ru'),
        'privacy' => $post->privacy,
        'author_id' => $post->author_id,
        'content' => $post->content,
    ]);

    $this->assertFalse(DB::table('categories_posts')->where('post_id', $post->id)->exists());

    Mail::assertNothingSent();
});

it('can author delete post', function () {
    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->public()
        ->count(3)
        ->create()
        ->pluck('id')
        ->all();

    /** @var User $user */
    $user = User::factory()->active()->create();

    /** @var Post $post */
    $post = Post::factory()->authoredBy($user->id)->create();
    $post->categories()->sync($categoriesIds);

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->fromRoute('admin.posts.show', ['post' => $post->id])
        ->delete(route('admin.posts.destroy', ['post' => $post->id]));

    $response->assertRedirectToRoute('admin.posts.index');
    $response->assertSessionHas(key: 'success', value: 'Пост успешно удалён');

    $this->assertDatabaseMissing('posts', [
        'title' => $post->title,
        'slug' => Str::slug(title: $post->title, language: 'ru'),
        'privacy' => $post->privacy,
        'author_id' => $post->author_id,
        'content' => $post->content,
    ]);

    $this->assertFalse(DB::table('categories_posts')->where('post_id', $post->id)->exists());

    Mail::assertNothingSent();
});

it('cannot active user delete another author post', function () {
    /** @var User $user */
    $user = User::factory()->active()->create();

    /** @var Post $post */
    $post = Post::factory()->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)->get(route('admin.posts.destroy', ['post' => $post->id]));

    $this->assertInstanceOf(AuthorizationException::class, $response->exception);
    $this->assertEquals('This action is unauthorized.', $response->exception->getMessage());
});

it('can send notification after deleting post', function () {
    /** @var Collection<User> $recipients */
    $recipients = User::factory()->active()->count(rand(5, 25))->create();

    /** @var array<int> $categoriesIds */
    $categoriesIds = Category::factory()
        ->count(3)
        ->create(['privacy' => Arr::random([null, PrivacyEnum::getRandomValue()])])
        ->pluck('id')
        ->all();

    /** @var User $user */
    $user = User::factory()
        ->when(
            value: fake()->boolean,
            callback: fn (UserFactory $factory) => $factory->admin(),
            default: fn (UserFactory $factory) => $factory->active(),
        )->create();

    /** @var Post $post */
    $post = Post::factory()
        ->when(
            value: ! $user->isAdmin(),
            callback: fn (PostFactory $factory) => $factory->authoredBy($user->id)
        )->create();
    $post->categories()->sync($categoriesIds);

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->fromRoute('admin.posts.index')
        ->delete(route('admin.posts.destroy', ['post' => $post->id]));

    $response->assertRedirectToRoute('admin.posts.index');
    $response->assertSessionHas(key: 'success', value: 'Пост успешно удалён');
    $this->assertDatabaseMissing('posts', [
        'title' => $post->title,
        'slug' => Str::slug(title: $post->title, language: 'ru'),
        'privacy' => $post->privacy,
        'author_id' => $post->author_id,
        'content' => $post->content,
    ]);

    $this->assertFalse(DB::table('categories_posts')->where('post_id', $post->id)->exists());

    $recipientsCount = $user->isAdmin() ? $recipients->count() + 1 : $recipients->count();
    /* размер чанка */
    $recipientsChunkSize = 20;
    /* разница между размером чанка и кол-вом получателей */
    $recipientsDiv = intdiv($recipientsCount, $recipientsChunkSize);

    Mail::assertQueuedCount($recipientsCount === $recipientsChunkSize ? $recipientsDiv : $recipientsDiv + 1);
    Mail::assertQueued(PostDeleted::class);
});
