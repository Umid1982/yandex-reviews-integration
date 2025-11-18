<?php

use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

    Route::get('/reviews', [ReviewController::class, 'list'])->name('api.reviews.list');
