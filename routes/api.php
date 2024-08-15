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
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\RecomendationPlaceController;
use App\Http\Controllers\TravelPlanController;
use App\Http\Middleware\Cors;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;


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
    Route::delete('user', [UserController::class, 'delete']);
    Route::delete('user/{id}', [UserController::class, 'adminDelete']);
});

// Social Routes
Route::get('login/{provider}', [SocialAuthController::class, 'redirectToProvider']);
Route::get('login/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);
Route::post('login/{provider}/callback', [SocialAuthController::class, 'handleProviderCallbackForMobile']);

// Show Images
Route::get('/uploads/{folder}/{filename}', function ( $folder, $filename)
{
    $path = storage_path('app/public/'. $folder. '/' . $filename);
    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});

// Travel Plans Routes
Route::middleware('auth:api')->group(function () {
    Route::get('travel-plans', [TravelPlanController::class, 'index']);
    Route::get('travel-plans/latest', [TravelPlanController::class, 'showSingle']);
    Route::get('travel-plans/{id}', [TravelPlanController::class, 'show']);
    Route::post('travel-plans', [TravelPlanController::class, 'store']);
    Route::put('travel-plans/{id}', [TravelPlanController::class, 'update']);
    Route::delete('travel-plans/{id}', [TravelPlanController::class, 'destroy']);
    Route::put('travel-plans/start-location/{id}', [TravelPlanController::class, 'updateStartLocation']);
});

// Destinations Routes
Route::middleware('auth:api')->group(function () {
    Route::get('travel-plans/{travelId}/destinations', [DestinationController::class, 'index']);
    Route::get('travel-plans/{travelId}/destinations/{destinationId}', [DestinationController::class, 'show']);
    Route::post('travel-plans/{travelId}/destinations', [DestinationController::class, 'store']);
    Route::put('travel-plans/{travelId}/destinations/{destinationId}', [DestinationController::class, 'update']);
    Route::delete('travel-plans/{travelId}/destinations/{destinationId}', [DestinationController::class, 'destroy']);
});

// Recomendations places route
Route::get('proxy/recomendation-places', [RecomendationPlaceController::class, 'getRecomendationPlace'])->middleware(Cors::class, 'auth:api');

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
Route::get('cultures-all', [CultureController::class, 'indexAll']);
