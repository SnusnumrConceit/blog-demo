<?php

use App\Http\Controllers\Site\CategoriesController;
use App\Http\Controllers\Site\CategoryController;
use App\Http\Controllers\Site\PostController;
use App\Http\Middleware\Site\Post\RuLocale;
use Illuminate\Support\Facades\Route;

Route::resource('categories', CategoryController::class)
    ->scoped(['category' => 'slug'])
    ->only(['index', 'show']);

Route::resource('posts', PostController::class)
    ->middleware(RuLocale::class)
    ->scoped(['post' => 'slug'])
    ->only(['index', 'show']);
