<?php

namespace Tests\Feature\Site\Category;

use \App\Enums\Post\PrivacyEnum as PostPrivacyEnum;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Database\Factories\CategoryFactory;
use Database\Factories\UserFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\TestCase;

it('can guest view public category', function () {
    /** @var Category $category */
    $category = Category::factory()->public()->create();

    /** @var TestCase $this */
    $response = $this->get(route('site.categories.show', ['category' => $category->slug]));

    $response->assertSuccessful();
    $response->assertViewIs('site.categories.show');

    /** @var Category $responseCategory */
    $responseCategory = $response->viewData('category');
    $this->assertEquals($category->slug, $responseCategory['slug']);
    $this->assertEquals($category->name, $responseCategory['name']);
    $this->assertEmpty($responseCategory['posts']);
});

it('can guest view public category with public posts', function () {
    /** @var Category $category */
    $category = Category::factory()->public()->create();
    /** @var Collection<Post> $posts */
    $posts = Post::factory()->count(15)->create();
    $availablePosts = $posts->filter(fn (Post $post) => is_null($post->privacy));

    $category->posts()->sync($posts->pluck('id')->all());

    /** @var TestCase $this */
    $response = $this->get(route('site.categories.show', ['category' => $category->slug]));

    $response->assertSuccessful();
    $response->assertViewIs('site.categories.show');
    /** @var Category $responseCategory */
    $responseCategory = $response->viewData('category');

    $this->assertEquals($category->slug, $responseCategory->slug);
    $this->assertEquals($category->name, $responseCategory->name);

    $this->assertCount(
        $availablePosts->whereNull('privacy')->count(),
        $responseCategory['posts']
    );

    foreach ($responseCategory['posts'] as $responsePost) {
        /** @var Post $post */
        $post = $availablePosts->where('slug', $responsePost->slug)->first();

        $this->assertNotNull($post);
        $this->assertEquals($post->title, $responsePost->title);
        $this->assertEquals($post->author_id, $responsePost->author_id);

        $author = $responsePost->author;
        $this->assertEquals($post->author->id, $author->id);
        $this->assertEquals($post->author->name, $author->name);
    }
});

it('cannot guest view protected category', function () {
    /** @var Category $category */
    $category = Category::factory()->protected()->create();

    /** @var TestCase $this */
    $response = $this->get(route('site.categories.show', ['category' => $category->slug]));

    $this->assertNotNull($response->exception);
    $this->assertInstanceOf(AuthorizationException::class, $response->exception);
});

it('cannot not admin view private category', function () {
    /** @var Category $category */
    $category = Category::factory()->private()->create();

    $users = [
        null,
        User::factory()->active()->create(),
        User::factory()->blocked()->create(),
        User::factory()->unverified()->create()
    ];

    foreach ($users as $user) {
        /** @var TestCase $this */
        $request = $user ? $this->actingAs($user) : $this;
        $response = $request
            ->get(route('site.categories.show', ['category' => $category->slug]));

        $this->assertNotNull($response->exception);
        $this->assertInstanceOf(AuthorizationException::class, $response->exception);
    }
});

it('can admin view private category', function () {
    $user = User::factory()->admin()->create();
    /** @var Category $category */
    $category = Category::factory()->private()->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->get(route('site.categories.show', ['category' => $category->slug]));

    $response->assertSuccessful();

    $response->assertViewIs('site.categories.show');
    /** @var Category $responseCategory */
    $responseCategory = $response->viewData('category');
    $this->assertEquals($category->slug, $responseCategory->slug);
    $this->assertEquals($category->name, $responseCategory->name);
    $this->assertEmpty($responseCategory->posts);
});

it('can active user view protected or public category', function () {
    $user = User::factory()->active()->create();
    /** @var Category $category */
    $category = Category::factory()
        ->when(
            value:true,
            callback: fn (CategoryFactory $factory) => $factory->protected(),
            default: fn (CategoryFactory $factory) => $factory->public(),
        )
        ->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->get(route('site.categories.show', ['category' => $category->slug]));

    $response->assertSuccessful();

    $response->assertViewIs('site.categories.show');
    /** @var Category $responseCategory */
    $responseCategory = $response->viewData('category');
    $this->assertEquals($category->slug, $responseCategory->slug);
    $this->assertEquals($category->name, $responseCategory->name);
    $this->assertEmpty($responseCategory->posts);
});

it('can admin/active user view protected or public category with protected posts', function () {
    /** @var User $user */
    $user = User::factory()
        ->when(
            value: fake()->boolean,
            callback: fn (UserFactory $factory) => $factory->admin(),
            default: fn (UserFactory $factory) => $factory->active(),
        )->create();

    /** @var Category $category */
    $category = Category::factory()
        ->when(
            value: fake()->boolean,
            callback: fn (CategoryFactory $factory) => $factory->protected(),
            default: fn (CategoryFactory $factory) => $factory->public(),
        )->create();

    /** @var Collection<Post> $posts */
    $posts = Post::factory()->count(15)->create();
    $category->posts()->sync($posts->pluck('id')->all());

    $availablePosts = $posts->filter(fn (Post $post) => $post->privacy != PostPrivacyEnum::PRIVATE);

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->get(route('site.categories.show', ['category' => $category->slug]));

    $response->assertSuccessful();

    $response->assertViewIs('site.categories.show');
    $responseCategory = $response->viewData('category');
    $this->assertEquals($category->slug, $responseCategory->slug);
    $this->assertEquals($category->name, $responseCategory->name);
    $this->assertCount($availablePosts->count(), $responseCategory->posts);

    foreach ($responseCategory->posts as $responsePost) {
        /** @var Post $post */
        $post = $availablePosts->where('slug', $responsePost->slug)->first();

        $this->assertNotNull($post);
        $this->assertEquals($post->title, $responsePost->title);
        $this->assertEquals($post->author_id, $responsePost->author_id);

        $author = $responsePost['author'];
        $this->assertEquals($post->author->id, $author->id);
        $this->assertEquals($post->author->name, $author->name);
    }
});
