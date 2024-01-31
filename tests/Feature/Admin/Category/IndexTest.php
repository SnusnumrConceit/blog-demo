<?php

namespace Tests\Feature\Admin\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

it('Cannot index categories', function () {
    $users = [
        null,
        User::factory()->active()->create(),
        User::factory()->blocked()->create(),
        User::factory()->unverified()->create()
    ];

    foreach ($users as $user) {
        /** @var TestCase $this */
        $response = is_null($user)
            ? $this->get(route('admin.categories.index'))
            : $this->actingAs($user)->get(route('admin.categories.index'));

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('Can admin index categories', function () {
    /** @var User $user */
    $user = User::factory()->admin()->create();

    Category::factory()->count(10)->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->get(route('admin.categories.index'));

    $response->assertSuccessful();
    $response->assertViewIs('admin.categories.index');
    $this->assertInstanceOf(Paginator::class, $response->viewData('categories'));
});
