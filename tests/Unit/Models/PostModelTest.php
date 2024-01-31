<?php

namespace Tests\Unit\Post;

use App\Models\Post;
use App\Services\CensorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\TestCase;

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

it('post has scopePublic', function () {
    /** @var Builder $query */
    $query = Post::public();

    /** @var TestCase $this */
    $where = head($query->getQuery()->wheres);

    $this->assertEquals('Null', $where['type']);
    $this->assertEquals('privacy', $where['column']);
    $this->assertEquals('and', $where['boolean']);
});

it('post has scopeProtected', function () {
    /** @var Builder $query */
    $query = Post::protected();

    /** @var TestCase $this */
    $where = head($query->getQuery()->wheres);

    $this->assertEquals('Basic', $where['type']);
    $this->assertEquals('privacy', $where['column']);
    $this->assertEquals('and', $where['boolean']);
});

it('post has scopePrivate', function () {
    /** @var Builder $query */
    $query = Post::private();

    /** @var TestCase $this */
    $where = head($query->getQuery()->wheres);

    $this->assertEquals('Basic', $where['type']);
    $this->assertEquals('privacy', $where['column']);
    $this->assertEquals('and', $where['boolean']);
});
