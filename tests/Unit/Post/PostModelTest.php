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
