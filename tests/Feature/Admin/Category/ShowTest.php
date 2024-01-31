<?php

namespace Tests\Feature\Admin\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

it('cannot not admin show category', function () {
    /** @var Category $category */
    $category = Category::factory()->create();

    $users = [
        null,
        User::factory()->active()->create(),
        User::factory()->blocked()->create(),
        User::factory()->unverified()->create()
    ];

    foreach ($users as $user) {
        /** @var TestCase $this */
        $response = is_null($user)
            ? $this->get(route('admin.categories.show', ['category' => $category->id]))
            : $this->actingAs($user)->get(route('admin.categories.show', ['category' => $category->id]));

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('can admin show category', function () {
    /** @var Category $category */
    $category = Category::factory()->create();

    /** @var User $user */
    $user = User::factory()->admin()->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->fromRoute('admin.categories.index')
        ->get(route('admin.categories.show', ['category' => $category->id]));

    $response->assertViewIs('admin.categories.show');
    $response->assertViewHas('category', $category);
});
