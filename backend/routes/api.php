<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\FoodController;
use App\Http\Controllers\Api\MealController;
use App\Http\Controllers\Api\ProposalController;
use App\Http\Controllers\Api\RevisionController;
use App\Http\Controllers\Api\SummaryController;
use App\Http\Controllers\Api\WeightController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/app', function () {
    return ['name' => config('app.name')];
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AccountController::class, 'show']);
    Route::put('/user', [AccountController::class, 'update']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::get('/settings', [SettingsController::class, 'show']);
    Route::put('/settings', [SettingsController::class, 'update']);

    Route::get('/weights', [WeightController::class, 'index']);
    Route::post('/weights', [WeightController::class, 'store']);
    Route::put('/weights/{weight}', [WeightController::class, 'update']);
    Route::delete('/weights/{weight}', [WeightController::class, 'destroy']);

    Route::get('/foods/search', [FoodController::class, 'search']);
    Route::get('/foods/{food}', [FoodController::class, 'show']);

    Route::get('/meals', [MealController::class, 'index']);
    Route::post('/meals', [MealController::class, 'store']);
    Route::get('/meals/{meal}', [MealController::class, 'show']);
    Route::put('/meals/{meal}', [MealController::class, 'update']);
    Route::delete('/meals/{meal}', [MealController::class, 'destroy']);

    Route::get('/activities', [ActivityController::class, 'index']);
    Route::post('/activities', [ActivityController::class, 'store']);
    Route::put('/activities/{activity}', [ActivityController::class, 'update']);
    Route::delete('/activities/{activity}', [ActivityController::class, 'destroy']);

    Route::get('/summary', SummaryController::class);

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'message']);

    Route::patch('/proposals/{proposal}', [ProposalController::class, 'update']);
    Route::post('/proposals/{proposal}/confirm', [ProposalController::class, 'confirm']);
    Route::post('/proposals/{proposal}/cancel', [ProposalController::class, 'cancel']);

    Route::post('/journal-revisions/{revision}/undo', [RevisionController::class, 'undo']);
});
