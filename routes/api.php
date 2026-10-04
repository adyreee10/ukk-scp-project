x<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TeacherScheduleController;
use App\Http\Controllers\Api\TeacherJournalController;
use App\Http\Controllers\Api\TeacherAttendanceController;
use App\Http\Controllers\Api\StudentScheduleController;
use App\Http\Controllers\Api\StudentAttendanceController;
use App\Http\Controllers\Api\StudentJournalController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\LeaveRequestVerificationController;
use App\Http\Controllers\Api\WakaClassController;
use App\Http\Controllers\Api\WakaConfigurationController;
use App\Http\Controllers\Api\WakaRoomController;
use App\Http\Controllers\Api\WakaUserController;


// =========================
// AUTH
// =========================

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


// =========================
// AUTHENTICATED USER
// =========================

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
});

// =========================
// WAKA
// =========================

Route::middleware(['auth:sanctum', 'role:Waka Kurikulum'])->prefix('waka')->group(function () {
        Route::get('/configuration', [
            WakaConfigurationController::class,
            'show',
        ]);

        Route::put('/configuration', [
            WakaConfigurationController::class,
            'update',
        ]);

        Route::apiResource('classes', WakaClassController::class)
            ->except('show')
            ->parameters(['classes' => 'schoolClass']);

        Route::apiResource('rooms', WakaRoomController::class)
            ->except('show');

        Route::apiResource('users', WakaUserController::class)
            ->except('show');
    });

// =========================
// TEACHER
// =========================

Route::middleware(['auth:sanctum', 'role:Guru'])->group(function () {

    Route::get('/teacher/dashboard', [
        \App\Http\Controllers\Api\DashboardController::class,
        'teacher'
    ]);
    Route::get('/teacher/schedules', [
        TeacherScheduleController::class,
        'index'
    ]);

    Route::post('/teacher/journals', [
        TeacherJournalController::class,
        'store'
    ]);

    Route::get('/teacher/journals', [
        TeacherJournalController::class,
        'index'
    ]);

    Route::get('/teacher/journals/{id}', [
        TeacherJournalController::class,
        'show'
    ]);

    Route::get('/teacher/attendances', [
        TeacherAttendanceController::class,
        'index'
    ]);
    
    Route::post('/teacher/attendances', [
        TeacherAttendanceController::class,
        'store'
    ]);
    
    Route::post('/teacher/schedules', [
        TeacherScheduleController::class,
        'store'
    ]);
    Route::put('teacher/schedules/{id}', [
        TeacherScheduleController::class,
        'update'
    ]);
    Route::delete('teacher/schedules/{id}', [
        TeacherScheduleController::class,
        'destroy'
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
        LeaveRequestController::class,
        'index'
    ]);

    Route::post('/student/leave-request', [
        LeaveRequestController::class,
        'store'
    ]);

    Route::get('/student/leave-request/{id}', [
        LeaveRequestController::class,
        'show'
    ]);

});

// =========================
// PARENT  
// =========================

Route::middleware(['auth:sanctum', 'role:Orang Tua'])->group(function() {

    Route::get('/parent/attendances/{student}', [
        \App\Http\Controllers\Api\ParentAttendanceController::class,
        'index'
    ]);

    Route::get('/parent/leave-request', [
        LeaveRequestController::class, 
        'index'
    ]);
    Route::post('/parent/leave-request', [
        LeaveRequestController::class,
        'store'
    ]);
    Route::get('/parent/leave-request/{id}', [
        LeaveRequestController::class,
        'show'
    ]);
});

// =========================
// VERIFIKASI IZIN STUDENT AFFAIRS
// =========================

Route::middleware(['auth:sanctum', 'role:Wali Kelas,Guru BK'])->group(function(){

    Route::get('/student-affairs/dashboard', [
        \App\Http\Controllers\Api\DashboardController::class,
        'waka'
    ]);
    
    Route::get('/student-affairs/attendances', [
        \App\Http\Controllers\Api\StudentAffairsAttendanceController::class,
        'index'
    ]);

    Route::get('/student-affairs/leave-request', [
        LeaveRequestVerificationController::class,
        'index'
    ]);

    Route::get('/student-affairs/leave-request/{id}', [
        LeaveRequestVerificationController::class,
        'show'
    ]);

    Route::put('/student-affairs/leave-request/{id}/approve', [
        LeaveRequestVerificationController::class,
        'approve'
    ]);

    Route::put('/student-affairs/leave-request/{id}/reject', [
        LeaveRequestVerificationController::class,
        'reject'
    ]);
});
