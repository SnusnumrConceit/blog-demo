<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Auth\AuthenticationException;
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

it('can access to admin dashboard', function () {
    /** @var User $user */
    $user = User::factory()->when(
        value: fake()->boolean,
        callback: fn (UserFactory $factory) => $factory->admin(),
        default: fn (UserFactory $factory) => $factory->active(),
    )->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->get(route('admin.dashboard'));

    $response->assertRedirectToRoute('admin.posts.index');
});
