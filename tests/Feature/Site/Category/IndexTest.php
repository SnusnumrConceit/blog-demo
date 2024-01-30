<?php

use App\Enums\Category\PrivacyEnum;
use App\Models\Category;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\TestCase;

it('can guest index public categories', function () {
    /** @var Category $categories */
    $categories = Category::factory()->count(20)->create();
    $availableCategories = $categories->filter(fn (Category $category) => is_null($category->privacy));

    /** @var TestCase $this */
    $response = $this->get(route('site.categories.index'));

    $response->assertSuccessful();

    foreach ($response->json('categories.data') as $responseCategory) {
        /** @var Category $category */
        $category = $availableCategories->where('slug', $responseCategory['slug'])->first();

        $this->assertNotNull($category);

        $this->assertEquals($category->name, $responseCategory['name']);
        $this->assertEmpty($responseCategory['posts']);
    }
});

it('can active/admin user index public and protected categories', function () {
    /** @var Category $categories */
    $categories = Category::factory()->count(20)->create();
    $availableCategories = $categories->filter(fn (Category $category) => $category != PrivacyEnum::PRIVATE);

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

    foreach ($response->json('categories.data') as $responseCategory) {
        /** @var Category $category */
        $category = $availableCategories->where('slug', $responseCategory['slug'])->first();

        $this->assertNotNull($category);

        $this->assertEquals($category->name, $responseCategory['name']);
        $this->assertEmpty($responseCategory['posts']);
    }
});
