<?php

use App\Http\Controllers\Site\PostController;
use App\Http\Middleware\Site\Post\RuLocale;
use Illuminate\Support\Facades\Route;

Route::resource('posts', PostController::class)
    ->middleware(RuLocale::class)
    ->scoped(['post' => 'slug'])
    ->only(['show']);
