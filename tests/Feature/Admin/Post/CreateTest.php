<?php

namespace Tests\Feature\Admin\Post;

use App\Enums\Post\PrivacyEnum;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
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
        /** @var TestCase $this */
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
    $categoryIds = Category::factory()->count(10)->create()->pluck('name', 'id')->all();
    $user = User::factory()->admin()->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->fromRoute('admin.posts.index')
        ->get(route('admin.posts.create'));

    $response->assertSuccessful();
    $response->assertViewIs('admin.posts.create');
    $response->assertViewHasAll(([
        'privacyItems' => [null, ...PrivacyEnum::getValues()],
        'categories' => $categoryIds
    ]));
});

it('can active user create post', function () {
    /** @var Category<Collection> $categories */
    $categories = Category::factory()->count(10)->create();
    $user = User::factory()->active()->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->fromRoute('admin.posts.index')
        ->get(route('admin.posts.create'));

    $response->assertSuccessful();
    $response->assertViewIs('admin.posts.create');
    $response->assertViewHasAll([
        'privacyItems' => [null, ...PrivacyEnum::getValues()],
        'categories' => $categories
            ->filter(fn (Category $category) => $category->privacy != PrivacyEnum::PRIVATE)
            ->pluck('name', 'id')->all()
    ]);
});
