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
        /** @var TestResponse $response */
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

    /** @var TestResponse $response */
    $response = $this->actingAs($user)->get(route('admin.categories.create'));

    $response->assertSuccessful();
    $response->assertContent(json_encode(['privacyItems' => PrivacyEnum::getValues()]));
});
