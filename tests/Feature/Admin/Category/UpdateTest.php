<?php

namespace Tests\Feature\Admin\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

it('can not update category', function () {
    /** @var Category $category */
    $category = Category::factory()->create();
    $payload = Category::factory()->make()->toArray();
    Arr::forget($payload, ['slug']);

    $users = [
        null,
        User::factory()->active()->create(),
        User::factory()->blocked()->create(),
        User::factory()->unverified()->create()
    ];

    foreach ($users as $user) {
        /** @var TestResponse $response */
        $response = is_null($user)
            ? $this->put(route('admin.categories.update', ['category' => $category->id]), $payload)
            : $this->actingAs($user)
                ->put(route('admin.categories.update', ['category' => $category->id]), $payload);

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('update category', function () {
    $user = User::factory()->admin()->create();

    /** @var Category $category */
    $category = Category::factory()->create();
    $payload = Category::factory()->make()->toArray();
    Arr::forget($payload, ['slug']);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)
        ->put(route('admin.categories.update', ['category' => $category->id]), $payload);

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseHas('categories', [
        'name' => $payload['name'],
        'privacy' => $payload['privacy'],
        'slug' => Str::slug(title: $payload['name'], language: 'ru')
    ]);
});

it('can not update category with invalid params', function () {
    $user = User::factory()->admin()->create();

    /** @var Category $category */
    $category = Category::factory()->create();
    $payload = Category::factory()->make()->toArray();

    $invalidParams = [
        'name' => [null, [], Str::random(101),],
        'privacy' => [Str::random(), [], fake()->randomDigit()],
    ];

    foreach ($invalidParams as $param => $values) {
        foreach ($values as $value) {
            /** @var TestResponse $response */
            $response = $this->actingAs($user)
                ->put(
                    route('admin.categories.update', ['category' => $category->id]),
                    array_merge($payload, [$param => $value])
                );

            $this->assertInstanceOf(ValidationException::class, $response->exception);
        }
    }
});

it('can not update category with another similar category', function () {
    $user = User::factory()->admin()->create();

    /** @var Category $similarCategory */
    $similarCategory = Category::factory()->create();
    $category = Category::factory()->create();

    $payload = array_merge([
        'privacy' => null,
        'name' => ucfirst($similarCategory->name),
    ]);

    /** @var TestResponse $response */
    $response = $this->actingAs($user)->put(route('admin.categories.update', ['category' => $category->id]), $payload);

    dump($response->exception);
    $this->assertInstanceOf(ValidationException::class, $response->exception);
});
