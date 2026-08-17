<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeacherScheduleController;
use App\Http\Controllers\Api\TeacherJournalController;
use App\Http\Controllers\Api\TeacherAttendanceController;
use App\Http\Controllers\Api\StudentScheduleController;
use App\Http\Controllers\Api\StudentAttendanceController;
use App\Http\Controllers\Api\StudentJournalController;
use App\Http\Controllers\Api\StudentLeaveRequestController;
use App\Http\Controllers\Api\StudentLeaveRequestVerificationController;


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

Route::middleware(['auth:sanctum', 'role:Guru'])->group(function () {

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

});

// =========================
// STUDENT
// =========================

Route::middleware(['auth:sanctum', 'role:Siswa'])->group(function () {

    Route::get('/student/schedules', [
        StudentScheduleController::class,
        'index'
    ]);

    Route::get('/student/attendances', [
        StudentAttendanceController::class,
        'index'
    ]);

    Route::get('/student/journals', [
        StudentJournalController::class,
        'index'
    ]);

    Route::get('/student/leave-request', [
        StudentLeaveRequestController::class,
        'index'
    ]);

    Route::post('/student/leave-request', [
        StudentLeaveRequestController::class,
        'store'
    ]);

    Route::get('/student/leave-request/{id}', [
        StudentLeaveRequestController::class,
        'show'
    ]);

});

// =========================
// KESISWAAN
// =========================

Route::middleware(['auth:sanctum', 'role:Kesiswaan'])->group(function(){

    Route::get('/student-affairs/leave-request', [
        StudentLeaveRequestVerificationController::class,
        'index'
    ]);

    Route::get('/student-affairs/leave-request/{id}', [
        StudentLeaveRequestVerificationController::class,
        'show'
    ]);

    Route::put('/student-affairs/leave-request/{id}/approve', [
        StudentLeaveRequestVerificationController::class,
        'approve'
    ]);

    Route::put('/student-affairs/leave-request/{id}/reject', [
        StudentLeaveRequestVerificationController::class,
        'reject'
    ]);
});
