<?php

namespace Tests\Feature\Admin\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

it('can not store category', function () {
    $payload = Category::factory()->make()->toArray();
    Arr::forget($payload, ['slug']);

    $users = [
        null,
        User::factory()->active()->create(),
        User::factory()->blocked()->create(),
        User::factory()->unverified()->create()
    ];

    foreach ($users as $user) {
        /** @var TestCase $this */
        $response = is_null($user)
            ? $this->post(route('admin.categories.store'), $payload)
            : $this->actingAs($user)->post(route('admin.categories.store'), $payload);

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('store category', function () {
    $user = User::factory()->admin()->create();

    $payload = Category::factory()->make()->toArray();
    Arr::forget($payload, ['slug']);

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->fromRoute('admin.categories.create')
        ->post(route('admin.categories.store'), $payload);

    /** @var Category $category */
    $category = Category::where(['slug' => Str::slug(title: $payload['name'], language: 'ru')])->first();

    $response->assertStatus(Response::HTTP_FOUND);
    $response->assertRedirectToRoute('admin.categories.show', ['category' => $category->id]);
    $response->assertSessionHas(key: 'success', value: 'Категория успешно создана');

    $this->assertDatabaseHas('categories', [
        'name' => $payload['name'],
        'privacy' => $payload['privacy'],
        'slug' => Str::slug(title: $payload['name'], language: 'ru')
    ]);
});

it('can not store category with invalid params', function () {
    $user = User::factory()->admin()->create();

    $payload = Category::factory()->make()->toArray();

    $invalidParams = [
        'name' => [null, [], Str::random(101),],
        'privacy' => [Str::random(), [], fake()->randomDigit()],
    ];

    foreach ($invalidParams as $param => $values) {
        foreach ($values as $value) {
            /** @var TestCase $this */
            $response = $this->actingAs($user)
                ->fromRoute('admin.categories.create')
                ->post(route('admin.categories.store'), array_merge($payload, [$param => $value]));

            $this->assertInstanceOf(ValidationException::class, $response->exception);
            $response->assertStatus(Response::HTTP_FOUND);
            $response->assertRedirectToRoute('admin.categories.create');
        }
    }
});

it('can not store category with duplicated slug category', function () {
    $user = User::factory()->admin()->create();

    /** @var Category $similarCategory */
    $similarCategory = Category::factory()->create();

    $payload = Category::factory()
        ->make(['name' => ucfirst($similarCategory->name)])
        ->toArray();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->fromRoute('admin.categories.create')
        ->post(route('admin.categories.store'), $payload);

    $this->assertInstanceOf(ValidationException::class, $response->exception);
    $response->assertRedirectToRoute('admin.categories.create');
});
