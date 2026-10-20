<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GuideController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app')->name('home');

Route::prefix('api')->group(function (): void {
    Route::get('/session', [AuthController::class, 'session']);
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('auth');

    Route::get('/games', [GameController::class, 'index']);
    Route::get('/games/{game}', [GameController::class, 'show'])->whereNumber('game');
    Route::get('/library', [GameController::class, 'library'])->middleware('auth');
    Route::put('/games/{game}/library', [GameController::class, 'updateLibrary'])->middleware('auth')->whereNumber('game');
    Route::put('/interests', [GameController::class, 'updateInterests'])->middleware('auth');

    Route::get('/guides', [GuideController::class, 'index']);
    Route::get('/guides/{guide}', [GuideController::class, 'show'])->whereNumber('guide');
    Route::post('/guides', [GuideController::class, 'store'])->middleware('auth');
    Route::post('/guides/{guide}/helpful', [GuideController::class, 'toggleHelpful'])->middleware('auth')->whereNumber('guide');
    Route::post('/guides/{guide}/save', [GuideController::class, 'toggleSaved'])->middleware('auth')->whereNumber('guide');
    Route::post('/guides/{guide}/comments', [GuideController::class, 'comment'])->middleware('auth')->whereNumber('guide');
    Route::get('/saved-guides', [GuideController::class, 'saved'])->middleware('auth');

    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store'])->middleware('auth');
    Route::get('/stats', [CommunityController::class, 'stats']);

    Route::prefix('admin')->middleware(['auth', 'admin'])->group(function (): void {
        Route::get('/', [AdminController::class, 'index']);
        Route::delete('/guides/{guide}', [AdminController::class, 'destroyGuide'])->whereNumber('guide');
        Route::delete('/reviews/{review}', [AdminController::class, 'destroyReview'])->whereNumber('review');
        Route::delete('/comments/{comment}', [AdminController::class, 'destroyComment'])->whereNumber('comment');
    });
});
