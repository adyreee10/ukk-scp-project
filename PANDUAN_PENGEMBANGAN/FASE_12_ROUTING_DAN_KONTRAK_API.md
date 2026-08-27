# Fase 12 — Routing dan Kontrak REST API Final

## Tujuan

Mengumpulkan seluruh endpoint dalam satu struktur yang konsisten dan memastikan role middleware benar.

## Middleware role

Perketat perbandingan pada `RoleMiddleware`:

```php
if (! $roleName || ! in_array($roleName, $roles, true)) {
    return response()->json([
        'message' => 'Anda tidak memiliki akses ke endpoint ini.',
    ], 403);
}
```

## Struktur `routes/api.php`

Gunakan struktur berikut:

```php
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\LeaveRequestVerificationController;
use App\Http\Controllers\Api\ParentAttendanceController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StudentAffairsAttendanceController;
use App\Http\Controllers\Api\StudentAttendanceController;
use App\Http\Controllers\Api\StudentJournalController;
use App\Http\Controllers\Api\StudentScheduleController;
use App\Http\Controllers\Api\TeacherAttendanceController;
use App\Http\Controllers\Api\TeacherJournalController;
use App\Http\Controllers\Api\TeacherScheduleController;
use App\Http\Controllers\Api\WakaClassController;
use App\Http\Controllers\Api\WakaConfigurationController;
use App\Http\Controllers\Api\WakaRoomController;
use App\Http\Controllers\Api\WakaUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    Route::middleware('role:Waka Kurikulum')->prefix('waka')->group(function () {
        Route::get('/configuration', [WakaConfigurationController::class, 'show']);
        Route::put('/configuration', [WakaConfigurationController::class, 'update']);
        Route::apiResource('/classes', WakaClassController::class)
            ->except('show')
            ->parameters(['classes' => 'schoolClass']);
        Route::apiResource('/rooms', WakaRoomController::class)->except('show');
        Route::apiResource('/users', WakaUserController::class)->except('show');
        Route::get('/dashboard', [DashboardController::class, 'waka']);
    });

    Route::middleware('role:Guru')->prefix('teacher')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'teacher']);
        Route::apiResource('/schedules', TeacherScheduleController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::apiResource('/journals', TeacherJournalController::class)
            ->only(['index', 'store', 'show']);
        Route::get('/attendances', [TeacherAttendanceController::class, 'index']);
        Route::post('/attendances', [TeacherAttendanceController::class, 'store']);
    });

    Route::middleware('role:Siswa')->prefix('student')->group(function () {
        Route::get('/schedules', [StudentScheduleController::class, 'index']);
        Route::get('/journals', [StudentJournalController::class, 'index']);
        Route::get('/attendances', [StudentAttendanceController::class, 'index']);
    });

    Route::middleware('role:Siswa,Orang Tua')->group(function () {
        Route::apiResource('/leave-requests', LeaveRequestController::class)
            ->only(['index', 'store', 'show']);
    });

    Route::middleware('role:Orang Tua')->prefix('parent')->group(function () {
        Route::get('/children/{student}/attendances', [ParentAttendanceController::class, 'index']);
    });

    Route::middleware('role:Wali Kelas,Guru BK')->prefix('student-affairs')->group(function () {
        Route::get('/leave-requests', [LeaveRequestVerificationController::class, 'index']);
        Route::get('/leave-requests/{id}', [LeaveRequestVerificationController::class, 'show']);
        Route::put('/leave-requests/{id}/approve', [LeaveRequestVerificationController::class, 'approve']);
        Route::put('/leave-requests/{id}/reject', [LeaveRequestVerificationController::class, 'reject']);
        Route::get('/attendance-recap', [StudentAffairsAttendanceController::class, 'index']);
    });
});
```

## Catatan route model binding

- `WakaClassController` memakai parameter `$schoolClass` karena resource parameter dipetakan eksplisit.
- Untuk route parent, jangan menerima injected `User $student` lalu langsung percaya. Tetap resolve dari `children()` agar ownership terjaga.
- Controller teacher memakai query ownership dan `findOrFail`, bukan model binding global yang membuka data user lain.

## Dokumentasi request penting

### Buat jadwal

```json
{
  "class_id": 1,
  "room_id": 1,
  "subject": "Pemrograman Web",
  "day_of_week": "Senin",
  "start_time": "07:00",
  "end_time": "08:30",
  "cycle_type": "Putaran 1"
}
```

### Buat jurnal

Gunakan `multipart/form-data`: `schedule_id`, `date`, `topic_description`, `photo`.

### Pengajuan izin

Gunakan `multipart/form-data`: `student_id` (wajib hanya untuk orang tua), `start_date`, `end_date`, `leave_type`, `reason`, `document`.

### Presensi massal

```json
{
  "journal_id": 10,
  "attendances": [
    {"student_id": 20, "status": "Hadir"},
    {"student_id": 21, "status": "Alpa"}
  ]
}
```

## Gerbang selesai

- [ ] Tidak ada route API register publik.
- [ ] Semua route privat berada di auth:sanctum.
- [ ] Semua aksi khusus role memiliki role middleware.
- [ ] URI memakai bentuk jamak konsisten.
- [ ] Mutation menggunakan POST/PUT/DELETE sesuai makna.
- [ ] Response 401/403/404/409/422 dapat dibedakan.

Commit:

```bash
git add routes/api.php app/Http/Middleware/RoleMiddleware.php
git commit -m "refactor: finalisasi routing dan kontrak REST SCP"
```
