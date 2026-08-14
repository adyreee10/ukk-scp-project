<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeacherScheduleController;
use App\Http\Controllers\Api\TeacherJournalController;
use App\Http\Controllers\Api\TeacherAttendanceController;
use App\Http\Controllers\Api\StudentScheduleController;


// =========================
// AUTH
// =========================

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


// =========================
// AUTHENTICATED USER
// =========================

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

});


// =========================
// TEACHER
// =========================

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/teacher/schedules', [
        TeacherScheduleController::class,
        'index'
    ]);

    Route::get('/teacher/journals', [
        TeacherJournalController::class,
        'index'
    ]);

    Route::get('/teacher/attendances', [
        TeacherAttendanceController::class,
        'index'
    ]);

    // STUDENT
    Route::get('/student/schedules', [
        StudentScheduleController::class,
        'index'
    ]);

});
