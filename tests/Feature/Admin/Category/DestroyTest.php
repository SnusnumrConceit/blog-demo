<?php

namespace Tests\Feature\Admin\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

it('cannot delete category', function () {
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
            ? $this->delete(route('admin.categories.destroy', ['category' => $category->id]))
            : $this->actingAs($user)->delete(route('admin.categories.destroy', ['category' => $category->id]));

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('delete category', function () {
    $user = User::factory()->admin()->create();

    /** @var Category $category */
    $category = Category::factory()->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->fromRoute('admin.categories.show', ['category' => $category->id])
        ->delete(route('admin.categories.destroy', ['category' => $category->id]));

    $response->assertStatus(Response::HTTP_FOUND);
    $response->assertRedirectToRoute('admin.categories.index');
    $response->assertSessionHas(key: 'success', value: 'Категория успешно удалена');

    $this->assertDatabaseMissing('categories', [
        'name' => $category->name,
        'slug' => Str::slug(title: $category->name, language: 'ru'),
        'privacy' => $category->privacy,
    ]);
});
