<?php

use App\Http\Controllers\Api\AcademicClassController;
use App\Http\Controllers\Api\AcademicSectionController;
use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassSubjectController;
use App\Http\Controllers\Api\DesignationController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\StudentGroupController;
use App\Http\Controllers\Api\SubjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::apiResource('schools', SchoolController::class)->only(['index', 'store']);
    Route::apiResource('schools.academic-classes', AcademicClassController::class)
        ->middleware('school.member');
    Route::apiResource('schools.academic-years', AcademicYearController::class)
        ->middleware('school.member');
    Route::apiResource('schools.academic-sections', AcademicSectionController::class)
        ->middleware('school.member');
    Route::apiResource('schools.class-subjects', ClassSubjectController::class)
        ->parameters(['class-subjects' => 'classSubject'])
        ->middleware('school.member');
    Route::apiResource('schools.designations', DesignationController::class)
        ->middleware('school.member');
    Route::apiResource('schools.employees', EmployeeController::class)
        ->middleware('school.member');
    Route::apiResource('schools.shifts', ShiftController::class)
        ->middleware('school.member');
    Route::apiResource('schools.student-groups', StudentGroupController::class)
        ->parameters(['student-groups' => 'studentGroup'])
        ->middleware('school.member');
    Route::apiResource('schools.subjects', SubjectController::class)
        ->middleware('school.member');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
