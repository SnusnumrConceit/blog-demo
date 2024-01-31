<?php

namespace Tests\Feature\Admin\Category;

use App\Enums\Category\PrivacyEnum;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

it('cannot create category', function () {
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
            ? $this->get(route('admin.categories.create'), $payload)
            : $this->actingAs($user)->get(route('admin.categories.create'), $payload);

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('create category', function () {
    $user = User::factory()->admin()->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->fromRoute('admin.categories.index')
        ->get(route('admin.categories.create'));

    $response->assertSuccessful();
    $response->assertViewIs('admin.categories.create');
    $response->assertViewHas(key: 'privacyItems', value: [null, ...PrivacyEnum::getValues()]);
});
