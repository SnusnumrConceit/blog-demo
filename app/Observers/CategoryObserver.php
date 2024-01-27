<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryObserver
{
    /**
     * @param Category $category
     *
     * @return void
     */
    public function saving(Category $category): void
    {
        if ($category->isDirty('name')) {
            $category->slug = Str::slug(title: $category->name, language: 'ru');
        }
    }
}
