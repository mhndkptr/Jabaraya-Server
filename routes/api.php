<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CultureController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;

// Auth Routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:api')->post('auth/logout', [AuthController::class, 'logout']);
Route::middleware('auth:api')->post('auth/token-update', [AuthController::class, 'tokenUpdate']);
Route::get('/unauthenticated', [AuthController::class, 'unauthenticated'])->name('login');

// User Routes
Route::middleware('auth:api')->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::get('user', [UserController::class, 'show']);
    Route::put('user', [UserController::class, 'update']);
    Route::post('user/change-password', [UserController::class, 'changePassword']);
});

// Social Routes
Route::get('login/{provider}', [SocialAuthController::class, 'redirectToProvider']);
Route::get('login/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);

// API Resources
// Route for categorys
Route::apiResource('categorys', CategoryController::class);
// Route for articles
Route::apiResource('articles', ArticleController::class);
Route::post('articles/upload-image', [ArticleController::class, 'uploadImage']);
// Route for news
Route::apiResource('news', NewsController::class);
Route::post('news/upload-image', [NewsController::class, 'uploadImage']);
// Route for events
Route::apiResource('events', EventController::class);
Route::post('events/upload-image', [EventController::class, 'uploadImage']);
// Route for cultures
Route::apiResource('cultures', CultureController::class);
Route::post('cultures/upload-image', [CultureController::class, 'uploadImage']);
