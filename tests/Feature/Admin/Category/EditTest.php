<?php

namespace Tests\Feature\Admin\Category;

use App\Enums\Category\PrivacyEnum;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

it('can not edit category', function () {
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
            ? $this->get(route('admin.categories.edit', ['category' => $category->id]), $payload)
            : $this->actingAs($user)->get(route('admin.categories.edit', ['category' => $category->id]), $payload);

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('edit category', function () {
    $user = User::factory()->admin()->create();

    /** @var Category $category */
    $category = Category::factory()->create();

    /** @var TestResponse $response */
    $response = $this->actingAs($user)->get(route('admin.categories.edit', ['category' => $category->id]));

    $response->assertSuccessful();
    $response->assertContent(json_encode([
        'privacyItems' => PrivacyEnum::getValues(),
        'category' => $category->toArray(),
    ]));
});
