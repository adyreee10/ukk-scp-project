<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ParentAttendanceController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TeacherScheduleController;
use App\Http\Controllers\Api\TeacherJournalController;
use App\Http\Controllers\Api\TeacherAttendanceController;
use App\Http\Controllers\Api\StudentAffairsAttendanceController;
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

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/login', [
        AuthController::class,
        'login'
    ]);

    Route::post('/forgot-password', [
        AuthController::class,
        'forgotPassword'
    ]);

    Route::post('/reset-password', [
        AuthController::class,
        'resetPassword'
    ]);
});

// =========================
// AUTHENTICATED USER
// =========================

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [
        AuthController::class, 
        'me']
    );

    Route::post('/logout', [
        AuthController::class, 
        'logout']
    );

    Route::put('/profile', [
        ProfileController::class, 
        'update']
    );

    Route::put('/profile/password', [
        ProfileController::class, 
        'updatePassword']
    );


// =========================
// WAKA
// =========================

Route::middleware('role:Waka Kurikulum')->prefix('waka')->group(function () {

        Route::get('/configuration', [
            WakaConfigurationController::class,
            'show',
        ]);

        Route::put('/configuration', [
            WakaConfigurationController::class,
            'update',
        ]);

        Route::apiResource('/classes', WakaClassController::class)
            ->except('show')
            ->parameters(['classes' => 'schoolClass']);

        Route::apiResource('/rooms', WakaRoomController::class)
            ->except('show');

        Route::apiResource('/users', WakaUserController::class)
            ->except('show');

        Route::get('/dashboard', [DashboardController::class, 'waka']);
    });

// =========================
// TEACHER
// =========================

Route::middleware('role:Guru')->prefix('teacher')->group(function () {

    Route::get('/dashboard', [
        DashboardController::class,
        'teacher'
    ]);

    Route::apiResource('/schedules', 
        TeacherScheduleController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::apiResource('/journals', 
        TeacherJournalController::class)
        ->only(['index', 'store', 'show']);

    Route::get('/attendances', [
        TeacherAttendanceController::class,
        'index'
    ]);
    
    Route::post('/attendances', [
        TeacherAttendanceController::class,
        'store'
    ]);

});

// =========================
// STUDENT
// =========================

Route::middleware('role:Siswa')->prefix('student')->group(function () {

    Route::get('/schedules', [
        StudentScheduleController::class,
        'index'
    ]);

    Route::get('/journals', [
        StudentJournalController::class,
        'index'
    ]);

    Route::get('/attendances', [
        StudentAttendanceController::class,
        'index'
    ]);
});

// =========================
// STUDENT & PARENT
// =========================

Route::middleware('role:Siswa,Orang Tua')->group(function () {

    Route::apiResource('/leave-requests', LeaveRequestController::class)
        ->only(['index', 'store', 'show']);
});

// =========================
// PARENT  
// =========================

Route::middleware('role:Orang Tua')->prefix('parent')->group(function() {

    Route::get('/children/{student}/attendances', [
        ParentAttendanceController::class,
        'index'
    ]);

});

// =========================
// VERIFIKASI IZIN STUDENT AFFAIRS guru bk and walkes
// =========================

Route::middleware('role:Wali Kelas,Guru BK')->prefix('student-affairs')->group(function(){

    Route::get('/leave-requests', [
        LeaveRequestVerificationController::class,
        'index'
    ]);

    Route::get('/leave-requests/{id}', [
        LeaveRequestVerificationController::class,
        'show'
    ]);

    Route::put('/leave-requests/{id}/approve', [
        LeaveRequestVerificationController::class,
        'approve'
    ]);

    Route::put('/leave-requests/{id}/reject', [
        LeaveRequestVerificationController::class,
        'reject'
    ]);

    Route::get('/attendance-recap', [
        StudentAffairsAttendanceController::class,
        'index'
    ]);
});
});