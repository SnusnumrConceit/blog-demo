<?php

namespace Tests\Unit\Jobs\Admin;

use App\Enums\PrivacyEnum;
use App\Jobs\Admin\Post\PublishPost;
use App\Models\Post;
use Illuminate\Support\Arr;

it('test handle publish post', function () {
    /** @var Post $post */
    $post = Post::factory()->create();

    $privacy = Arr::random([null, PrivacyEnum::getRandomValue()]);
    while($privacy === $post->privacy) {
        $privacy = Arr::random([null, PrivacyEnum::getRandomValue()]);
    }

    $job = new PublishPost(
        postId: $post->id,
        privacy: $privacy
    );

    $job->handle();

    $post->refresh();
    $this->assertEquals($post->privacy, $privacy);
});
