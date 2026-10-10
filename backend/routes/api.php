<?php

use App\Http\Controllers\AccountController;
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
});
