<?php

use App\Enums\PrivacyEnum;
use App\Models\Category;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Pagination\LengthAwarePaginator;

it ('can nobody index private categories', function () {
    /** @var Category $categories */
    Category::factory()->private()->count(20)->create();

    $users = [
        null,
        User::factory()->admin()->create(),
        User::factory()->active()->create(),
        User::factory()->blocked()->create(),
        User::factory()->unverified()->create(),
    ];

    foreach ($users as $user) {
        /** @var TestCase $this */
        $response = ! $user
            ? $this->get(route('site.categories.index'))
            : $this->actingAs($user)->get(route('site.categories.index'));

        $response->assertSuccessful();
        $response->assertViewIs('site.categories.index');
        /** @var LengthAwarePaginator $responseCategories */
        $responseCategories = $response->viewData('categories');
        $this->assertTrue($responseCategories->isEmpty());
    }
});

it('can guest index public categories', function () {
    /** @var Category $categories */
    $categories = Category::factory()->count(20)->create();
    $availableCategories = $categories->filter(fn (Category $category) => is_null($category->privacy));

    /** @var TestCase $this */
    $response = $this->get(route('site.categories.index'));

    $response->assertSuccessful();

    $response->assertViewIs('site.categories.index');
    /** @var LengthAwarePaginator $responseCategories */
    $responseCategories = $response->viewData('categories');
    $this->assertCount($responseCategories->total(), $availableCategories);
});

it('can active/admin user index public and protected categories', function () {
    /** @var Category $categories */
    $categories = Category::factory()->count(20)->create();
    $availableCategories = $categories->filter(fn (Category $category) => $category->privacy != PrivacyEnum::PRIVATE->value);

    $user = User::factory()
        ->when(
            value: fake()->boolean,
            callback: fn (UserFactory $factory) => $factory->admin(),
            default: fn (UserFactory $factory) => $factory->active()
        )->create();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->get(route('site.categories.index'));

    $response->assertSuccessful();

    $response->assertViewIs('site.categories.index');

    /** @var LengthAwarePaginator $responseCategories */
    $responseCategories = $response->viewData('categories');
    $this->assertCount($responseCategories->total(), $availableCategories);
});
