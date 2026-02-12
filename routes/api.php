<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingsController;

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('settings', [SettingsController::class, 'show'])->middleware('auth:sanctum');
Route::post('settings', [SettingsController::class, 'save'])->middleware('auth:sanctum');
Route::post('import', [SettingsController::class, 'import'])->middleware('auth:sanctum');
