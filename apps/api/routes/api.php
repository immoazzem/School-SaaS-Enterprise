<?php

use App\Http\Controllers\Api\AcademicClassController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SchoolController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::apiResource('schools', SchoolController::class)->only(['index', 'store']);
    Route::apiResource('schools.academic-classes', AcademicClassController::class);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
