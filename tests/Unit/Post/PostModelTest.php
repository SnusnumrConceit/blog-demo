<?php

namespace Tests\Unit\Post;

use App\Models\Post;
use App\Services\CensorService;

it('post content censor', function () {
    /** @var Post $post */
    $post = Post::factory()->create([
        'content' => 'бля как же охуенно писать автотесты'
    ]);

    $this->assertEquals(
        CensorService::censor($post->content),
        $post->censored_content
    );
});

it('post is public', function () {
    /** @var Post $post */
    $post = Post::factory()->public()->create();

    $this->assertTrue($post->isPublic());
});

it('post is protected', function () {
    /** @var Post $post */
    $post = Post::factory()->protected()->create();

    $this->assertTrue($post->isProtected());
});

it('post is private', function () {
    /** @var Post $post */
    $post = Post::factory()->private()->create();

    $this->assertTrue($post->isPrivate());
});
