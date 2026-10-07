<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\Web\DashboardController; // <--- Pastikan import ini ada di atas

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rute Waka Kurikulum
Route::middleware(['auth', 'role:Waka Kurikulum'])->group(function () {
    Route::get('/waka/dashboard', function () {
        return view('dashboards.waka');
    })->name('waka.dashboard');
});

// Rute Guru
Route::middleware(['auth', 'role:Guru'])->group(function () {
    Route::get('/teacher/dashboard', function () {
        // Data sementara agar template tidak error
        $schedules = collect([]); 
        $configuration = \App\Models\SystemConfiguration::first() ?? (object)['is_schedule_locked' => false];
        return view('dashboards.teacher', compact('schedules', 'configuration'));
    })->name('teacher.dashboard');
});

// Rute Wali Kelas / Guru BK
Route::middleware(['auth', 'role:Wali Kelas,Guru BK'])->group(function () {
    Route::get('/student-affairs/dashboard', function () {
        return view('dashboards.student-affairs');
    })->name('student-affairs.dashboard');
});

// Rute Siswa / Orang Tua
Route::middleware(['auth', 'role:Siswa,Orang Tua'])->group(function () {
    Route::get('/family/dashboard', function () {
        return view('dashboards.family');
    })->name('family.dashboard');
});


require __DIR__.'/auth.php';
