<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\Admin\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::get('/', AdminController::class)
    ->name('dashboard');

Route::resource('categories', CategoryController::class)
    ->whereNumber('category')
    ->middleware(AdminOnly::class);

Route::resource('posts', PostController::class)
    ->whereNumber('post');
