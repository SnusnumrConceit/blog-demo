<?php

namespace Tests\Feature\Site;

use Illuminate\Foundation\Testing\TestCase;

it('can welcome page redirect to posts', function () {
    /** @var TestCase $this */
   $response = $this->get('/');

   $response->assertRedirectToRoute('site.posts.index');
});
