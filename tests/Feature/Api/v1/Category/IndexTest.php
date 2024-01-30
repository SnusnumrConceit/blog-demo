<?php

namespace Tests\Feature\Api\Category;

use App\Models\Category;
use Illuminate\Testing\TestResponse;

it('Can index categories', function () {
    $categories = Category::factory()->count(20)->create();
    $publicCategoriesCount = $categories->whereNull('privacy')->count();

    /** @var TestResponse $response */
    $response = $this->getJson(route('api.v1.categories.index'));
    $perPage = 15;

    $response->assertJson(
        value: [
            'categories' => $categories->whereNull('privacy')->only(['slug', 'name'])->all(),
                'links' => [
                    'first' => route('api.v1.categories.index', ['page' => 1]),
                    "last" => null,
                    "prev" => null,
                    "next" => null,
                ],
                'meta' => [
                    'current_page' => 1,
                    'from' => 1,
                    'path' => route('api.v1.categories.index'),
                    'per_page' => $perPage,
                    'to' => min($publicCategoriesCount, $perPage),
                ],
            ],
        strict: true
    );
});

it('Can index categories from page#2', function () {
    $categories = Category::factory()->public()->count(20)->create();
    $perPage = 15;

    /** @var TestResponse $response */
    $response = $this->getJson(route('api.v1.categories.index', ['page' => 2]));

    $response->assertJson(
        value: [
            'categories' => $categories->whereNull('privacy')->only(['slug', 'name'])->all(),
            'links' => [
                'first' => route('api.v1.categories.index', ['page' => 1]),
                "last" => null,
                "prev" => route('api.v1.categories.index', ['page' => 1]),
                "next" => null,
            ],
            'meta' => [
                'current_page' => 2,
                'from' => 16,
                'path' => route('api.v1.categories.index'),
                'per_page' => $perPage,
                'to' => $categories->count(),
            ],
        ],
        strict: true
    );
});
