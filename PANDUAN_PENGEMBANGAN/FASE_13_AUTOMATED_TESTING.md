# Fase 13 — Automated Testing Acceptance Criteria

## Tujuan

Membuktikan aturan bisnis dengan Pest. Karena panduan dibuat tanpa mengeksekusi kode, test inilah gerbang objektif yang wajib kamu jalankan. Jangan menyatakan fitur bebas bug hanya dari pengujian manual.

## 1. Helper test

Tambahkan pada `tests/Pest.php`:

```php
use App\Models\Role;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function userWithRole(string $roleName, array $attributes = []): User
{
    $role = Role::firstOrCreate(['role_name' => $roleName]);

    return User::factory()->create([
        'role_id' => $role->id,
        'username' => fake()->unique()->userName(),
        ...$attributes,
    ]);
}

function actingAsRole(string $roleName, array $attributes = []): User
{
    $user = userWithRole($roleName, $attributes);
    Sanctum::actingAs($user);
    return $user;
}
```

Pastikan `UserFactory` menghasilkan `username`; bila belum:

```php
'username' => fake()->unique()->userName(),
```

## 2. Test autentikasi dan RBAC

Buat `tests/Feature/Api/AuthenticationApiTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('issues a sanctum token for valid credentials', function () {
    $user = userWithRole('Guru', [
        'username' => 'guru.test',
        'password' => Hash::make('password123'),
    ]);

    $this->postJson('/api/login', [
        'username' => 'guru.test',
        'password' => 'password123',
        'device_name' => 'pest',
    ])->assertOk()->assertJsonStructure(['token', 'token_type', 'user']);

    expect($user->tokens()->count())->toBe(1);
});

it('rejects unauthenticated and wrong-role access', function () {
    $this->getJson('/api/teacher/schedules')->assertUnauthorized();

    actingAsRole('Siswa');
    $this->getJson('/api/teacher/schedules')->assertForbidden();
});
```

Gunakan trait `RefreshDatabase` untuk seluruh Feature API melalui `uses(RefreshDatabase::class)->in('Feature/Api');` di Pest.php.

## 3. Test konflik, batas waktu, dan lock

Buat `ScheduleBusinessRulesTest.php`. Siapkan role, teacher, kelas, ruang, config id 1, lalu:

```php
<?php

use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;

it('rejects overlap and accepts touching time boundaries', function () {
    $teacher = actingAsRole('Guru');
    $class = SchoolClass::create(['class_name' => 'X RPL 1']);
    $room = Room::create(['room_name' => 'Lab RPL']);
    SystemConfiguration::create([
        'id' => 1,
        'active_cycle' => 'Putaran 1',
        'is_schedule_locked' => false,
        'updated_by' => $teacher->id,
    ]);

    $base = [
        'class_id' => $class->id,
        'room_id' => $room->id,
        'subject' => 'Web',
        'day_of_week' => 'Senin',
        'cycle_type' => 'Putaran 1',
    ];

    $this->postJson('/api/teacher/schedules', [...$base, 'start_time' => '08:00', 'end_time' => '10:00'])
        ->assertCreated();

    $this->postJson('/api/teacher/schedules', [...$base, 'start_time' => '09:00', 'end_time' => '11:00'])
        ->assertUnprocessable();

    $this->postJson('/api/teacher/schedules', [...$base, 'start_time' => '10:00', 'end_time' => '12:00'])
        ->assertCreated();
});

it('blocks all schedule mutations when locked', function () {
    $teacher = actingAsRole('Guru');
    $class = SchoolClass::create(['class_name' => 'XI RPL 1']);
    $room = Room::create(['room_name' => 'Ruang 101']);
    SystemConfiguration::create([
        'id' => 1,
        'active_cycle' => 'Putaran 1',
        'is_schedule_locked' => true,
        'updated_by' => $teacher->id,
    ]);

    $schedule = Schedule::create([
        'teacher_id' => $teacher->id,
        'class_id' => $class->id,
        'room_id' => $room->id,
        'subject' => 'Web',
        'day_of_week' => 'Senin',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'cycle_type' => 'Putaran 1',
    ]);

    $payload = [
        'class_id' => $class->id,
        'room_id' => $room->id,
        'subject' => 'Web Lanjut',
        'day_of_week' => 'Senin',
        'start_time' => '10:00',
        'end_time' => '12:00',
        'cycle_type' => 'Putaran 1',
    ];

    $this->postJson('/api/teacher/schedules', $payload)->assertForbidden();
    $this->putJson("/api/teacher/schedules/{$schedule->id}", $payload)->assertForbidden();
    $this->deleteJson("/api/teacher/schedules/{$schedule->id}")->assertForbidden();
});
```

Tambahkan kasus konflik karena teacher sama, class sama, room sama, serta non-konflik pada hari/cycle berbeda.

## 4. Test sinkronisasi izin

Buat `LeaveSynchronizationTest.php` dengan tiga assertion waktu state:

```php
expect(Attendance::count())->toBe(0);

$this->putJson("/api/student-affairs/leave-requests/{$leave->id}/approve")
    ->assertOk();

expect(Attendance::count())->toBe(0); // aturan paling penting

// Login sebagai guru dan POST multipart jurnal untuk schedule/tanggal leave.
$this->post('/api/teacher/journals', [
    'schedule_id' => $schedule->id,
    'date' => $date,
    'topic_description' => 'Materi pengujian',
    'photo' => UploadedFile::fake()->image('kelas.jpg'),
], ['Accept' => 'application/json'])->assertCreated();

expect(Attendance::where([
    'student_id' => $student->id,
    'status' => 'Sakit',
])->count())->toBe(1);
```

Tambahkan test Pending, Rejected, tanggal di luar rentang, kelas lain, Izin vs Sakit, dan idempotensi.

## 5. Test upload

Gunakan:

```php
Storage::fake('public');
```

Kasus wajib:

- foto valid tersimpan;
- foto tidak ada ditolak 422;
- PDF sebagai foto ditolak;
- foto >2 MB ditolak;
- dokumen izin PDF valid;
- executable atau file >4 MB ditolak;
- file dibersihkan bila database gagal.

## 6. Test reminder dengan waktu beku

```php
use Illuminate\Support\Carbon;

Carbon::setTestNow('2026-08-24 10:30:00');
// Jadwal Senin berakhir 10:00: tepat 30 menit, reminder belum muncul.

Carbon::setTestNow('2026-08-24 10:31:00');
// Reminder muncul bila jurnal belum ada.

// Buat jurnal tanggal 2026-08-24, lalu assert reminder kosong.
Carbon::setTestNow();
```

Reset waktu pada akhir test atau gunakan `travelTo()`/`travelBack()` agar test lain tidak terkontaminasi.

## 7. Matriks minimum test

| Kelompok | Minimum kasus |
|---|---:|
| Auth & profile | 8 |
| RBAC & ownership | 12 |
| Waka config/master | 10 |
| Schedule/conflict/lock/cycle | 18 |
| Journal/upload | 10 |
| Leave siswa/orang tua | 12 |
| Verification scope/state | 10 |
| Attendance/sync | 14 |
| Recap/reminder | 10 |
| Total target | **104** |

Jumlah bukan tujuan utama; setiap AC-001 sampai AC-018 minimal mempunyai satu test positif dan satu test negatif bila relevan.

## Perintah yang kamu jalankan

```bash
php artisan test
php artisan test --filter=ScheduleBusinessRulesTest
php artisan test --filter=LeaveSynchronizationTest
```

## Gerbang selesai

- [ ] Semua AC-001–AC-018 ditautkan ke test.
- [ ] Test gagal jika query overlap dibalik.
- [ ] Test gagal jika approval langsung membuat attendance.
- [ ] Test gagal jika lock hanya diterapkan di UI.
- [ ] Test tidak bergantung waktu nyata atau data seeder.
- [ ] Seluruh suite lulus berulang kali.

Commit:

```bash
git add tests database/factories
git commit -m "test: buktikan acceptance criteria inti SCP"
```
