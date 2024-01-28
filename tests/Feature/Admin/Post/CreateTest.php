<?php

namespace Tests\Feature\Admin\Post;

use App\Enums\Post\PrivacyEnum;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

it('cannot create post', function () {
    $payload = Post::factory()->simple()->make()->toArray();
    Arr::forget($payload, ['slug', 'author_id']);

    $users = [
        null,
        User::factory()->blocked()->create(),
        User::factory()->unverified()->create()
    ];

    foreach ($users as $user) {
        /** @var TestResponse $response */
        $response = is_null($user)
            ? $this->get(route('admin.posts.create'), $payload)
            : $this->actingAs($user)->get(route('admin.posts.create'), $payload);

        if (! $user) {
            $this->assertInstanceOf(AuthenticationException::class, $response->exception);
            return;
        }

        $this->assertInstanceOf(AccessDeniedHttpException::class, $response->exception);
        $this->assertEquals('Доступ запрещён', $response->exception->getMessage());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->exception->getStatusCode());
    }
});

it('can admin create post', function () {
    $user = User::factory()->admin()->create();

    /** @var TestResponse $response */
    $response = $this->actingAs($user)->get(route('admin.posts.create'));

    $response->assertSuccessful();
    $response->assertContent(json_encode(['privacyItems' => PrivacyEnum::getValues()]));
});

it('can active user create post', function () {
    $user = User::factory()->active()->create();

    /** @var TestResponse $response */
    $response = $this->actingAs($user)->get(route('admin.posts.create'));

    $response->assertSuccessful();
    $response->assertContent(json_encode(['privacyItems' => PrivacyEnum::getValues()]));
});
