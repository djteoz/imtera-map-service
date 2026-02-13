<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ReviewsController;

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Auth-protected endpoints (existing)
Route::get('settings', [SettingsController::class, 'show'])->middleware('auth:sanctum');
Route::post('settings', [SettingsController::class, 'save'])->middleware('auth:sanctum');
Route::post('import', [SettingsController::class, 'import'])->middleware('auth:sanctum');
Route::get('reviews', [ReviewsController::class, 'index'])->middleware('auth:sanctum');
Route::get('reviews/{id}', [ReviewsController::class, 'show'])->middleware('auth:sanctum');
Route::patch('reviews/{id}', [ReviewsController::class, 'update'])->middleware('auth:sanctum');

// Temporary public demo endpoints (no auth) — used for UI preview without login
Route::get('public/settings', [SettingsController::class, 'show']);
Route::post('public/settings', [SettingsController::class, 'save']);
Route::post('public/import', [SettingsController::class, 'import']);
Route::get('public/reviews', [ReviewsController::class, 'index']);
Route::get('public/reviews/{id}', [ReviewsController::class, 'show']);
Route::patch('public/reviews/{id}', [ReviewsController::class, 'update']);
