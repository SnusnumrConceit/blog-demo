<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Middleware\Admin\AdminAccess;
use Illuminate\Support\Facades\Route;

Route::resource('categories', CategoryController::class)
    ->whereNumber('category')
    ->middleware(AdminAccess::class);

Route::resource('posts', PostController::class)
    ->whereNumber('post');
