<?php

use App\Enums\Post\PrivacyEnum;
use App\Models\Post;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\TestCase;

it('can guest index public posts', function () {
    /** @var Collection<Post> $posts */
    $posts = Post::factory()->count(20)->create();
    $availablePosts = $posts->filter(fn (Post $post) => is_null($post->privacy));

    /** @var TestCase $this */
    $response = $this->get(route('site.posts.index'), ['Accept-Language' => 'ru-RU']);

    foreach ($response->json('posts.data') as $responsePost) {
        /** @var Post $post */
        $post = $availablePosts->where('slug', $responsePost['slug'])->first();

        $this->assertNotNull($post);
        $this->assertEquals($post->title, $responsePost['title']);
        $this->assertEquals($post->author_id, $responsePost['author_id']);

        $author = $responsePost['author'];
        $this->assertEquals($post->author->id, $author['id']);
        $this->assertEquals($post->author->name, $author['name']);
    }

    $response->assertSuccessful();
});

it('can active/admin user index public and protected posts', function () {
    /** @var Collection<Post> $posts */
    $posts = Post::factory()->count(20)->create();
    $availablePosts = $posts->filter(fn (Post $post) => $post != PrivacyEnum::PRIVATE);

    $user = User::factory()
        ->when(
            value: fake()->boolean,
            callback: fn (UserFactory $factory) => $factory->admin(),
            default: fn (UserFactory $factory) => $factory->active()
        )->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)->get(route('site.posts.index'), ['Accept-Language' => 'ru-RU']);

    $response->assertSuccessful();

    foreach ($response->json('posts.data') as $responsePost) {
        /** @var Post $post */
        $post = $availablePosts->where('slug', $responsePost['slug'])->first();

        $this->assertNotNull($post);
        $this->assertEquals($post->title, $responsePost['title']);
        $this->assertEquals($post->author_id, $responsePost['author_id']);

        $author = $responsePost['author'];
        $this->assertEquals($post->author->id, $author['id']);
        $this->assertEquals($post->author->name, $author['name']);
    }
});
