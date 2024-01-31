<?php

namespace Tests\Feature\Admin\Post;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

it('Cannot index posts', function () {
    $users = [
        null,
        User::factory()->blocked()->create(),
        User::factory()->unverified()->create()
    ];

    foreach ($users as $user) {
        /** @var TestCase $this */
        $response = is_null($user)
            ? $this->get(route('admin.posts.index'))
            : $this->actingAs($user)->get(route('admin.posts.index'));

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('Can admin index posts', function () {
    /** @var User $user */
    $user = User::factory()->admin()->create();

    Post::factory()->count(10)->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->get(route('admin.posts.index'));

    $response->assertSuccessful();
    $response->assertViewIs('admin.posts.index');
    $this->assertInstanceOf(Paginator::class, $response->viewData('posts'));
});
