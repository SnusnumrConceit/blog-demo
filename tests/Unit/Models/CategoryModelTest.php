<?php

namespace Tests\Unit\Category;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\TestCase;

it('category is public', function () {
    /** @var Category $category */
    $category = Category::factory()->public()->create();

    $this->assertTrue($category->isPublic());
});

it('category is protected', function () {
    /** @var Category $category */
    $category = Category::factory()->protected()->create();

    $this->assertTrue($category->isProtected());
});

it('category is private', function () {
    /** @var Category $category */
    $category = Category::factory()->private()->create();

    $this->assertTrue($category->isPrivate());
});

it('category has scopePublic', function () {
    /** @var Builder $query */
    $query = Category::public();

    /** @var TestCase $this */
    $where = head($query->getQuery()->wheres);

    $this->assertEquals('Null', $where['type']);
    $this->assertEquals('privacy', $where['column']);
    $this->assertEquals('and', $where['boolean']);
});

it('category has scopeProtected', function () {
    /** @var Builder $query */
    $query = Category::protected();

    /** @var TestCase $this */
    $where = head($query->getQuery()->wheres);

    $this->assertEquals('Basic', $where['type']);
    $this->assertEquals('privacy', $where['column']);
    $this->assertEquals('and', $where['boolean']);
});

it('category has scopePrivate', function () {
    /** @var Builder $query */
    $query = Category::private();

    /** @var TestCase $this */
    $where = head($query->getQuery()->wheres);

    $this->assertEquals('Basic', $where['type']);
    $this->assertEquals('privacy', $where['column']);
    $this->assertEquals('and', $where['boolean']);
});
