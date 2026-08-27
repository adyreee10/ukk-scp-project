# Fase 03 — Model Domain, Cast, Relasi, dan Scope

## Tujuan

Memusatkan pengetahuan domain di model agar controller tidak mengulang string dan query yang rawan salah.

## 1. `app/Models/User.php`

Pertahankan trait Sanctum, lalu pastikan bagian domainnya seperti ini:

```php
protected $fillable = [
    'role_id',
    'class_id',
    'homeroom_class_id',
    'parent_id',
    'name',
    'username',
    'email',
    'password',
];

public function role()
{
    return $this->belongsTo(Role::class);
}

public function schoolClass()
{
    return $this->belongsTo(SchoolClass::class, 'class_id');
}

public function homeroomClass()
{
    return $this->belongsTo(SchoolClass::class, 'homeroom_class_id');
}

public function parent()
{
    return $this->belongsTo(User::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(User::class, 'parent_id');
}

public function submittedLeaveRequests()
{
    return $this->hasMany(LeaveRequest::class, 'applicant_id');
}

public function hasRole(string ...$roles): bool
{
    return in_array($this->role?->role_name, $roles, true);
}
```

Hapus relasi `journals()` dari User karena tabel journals tidak mempunyai `teacher_id`; jurnal diakses melalui schedule.

## 2. `app/Models/SystemConfiguration.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    public const CYCLES = ['Putaran 1', 'Putaran 2'];

    protected $fillable = ['active_cycle', 'is_schedule_locked', 'updated_by'];

    protected function casts(): array
    {
        return ['is_schedule_locked' => 'boolean'];
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): self
    {
        return static::query()->findOrFail(1);
    }
}
```

## 3. `app/Models/Schedule.php`

```php
public const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

protected $fillable = [
    'teacher_id', 'class_id', 'room_id', 'subject',
    'day_of_week', 'start_time', 'end_time', 'cycle_type',
];

public function scopeActiveCycle($query)
{
    return $query->where(
        'cycle_type',
        SystemConfiguration::current()->active_cycle,
    );
}
```

Relasi `teacher`, `schoolClass`, `room`, dan `journals` yang sudah ada tetap dipertahankan.

## 4. `app/Models/Journal.php`

```php
protected $fillable = ['schedule_id', 'date', 'topic_description', 'photo_path'];

protected function casts(): array
{
    return ['date' => 'date'];
}
```

Relasi `schedule()` dan `attendances()` tetap dipertahankan.

## 5. `app/Models/Attendance.php`

```php
public const STATUSES = ['Hadir', 'Izin', 'Sakit', 'Alpa'];

protected $fillable = ['journal_id', 'student_id', 'status', 'date'];

protected function casts(): array
{
    return ['date' => 'date'];
}
```

## 6. `app/Models/LeaveRequest.php`

```php
public const TYPES = ['Izin', 'Sakit'];
public const APPROVALS = ['Pending', 'Approved', 'Rejected'];

protected $fillable = [
    'student_id', 'applicant_id', 'verified_by',
    'start_date', 'end_date', 'leave_type', 'reason',
    'document_path', 'status_approval',
];

protected function casts(): array
{
    return [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}

public function scopeApprovedOn($query, string $date)
{
    return $query
        ->where('status_approval', 'Approved')
        ->whereDate('start_date', '<=', $date)
        ->whereDate('end_date', '>=', $date);
}
```

Relasi `student`, `applicant`, dan `verifier` tetap dipertahankan.

## 7. `SchoolClass` dan `Room`

Tambahkan relasi kelas perwalian:

```php
// SchoolClass.php
protected $table = 'classes';

public function homeroomTeachers()
{
    return $this->hasMany(User::class, 'homeroom_class_id');
}
```

## Catatan bug yang dicegah

- `hasRole()` memakai strict comparison.
- `current()` gagal jelas bila seeder konfigurasi belum dijalankan; tidak diam-diam menciptakan konfigurasi tanpa updater.
- tanggal dicast menjadi Carbon sehingga serialisasi dan perbandingan konsisten.
- konstanta menjadi satu sumber validasi.
- jurnal tidak lagi seolah mempunyai foreign key teacher langsung.

## Gerbang selesai

- [ ] Tidak ada relasi yang menunjuk kolom yang tidak tersedia.
- [ ] Semua tanggal dan boolean mempunyai cast.
- [ ] Role, status, cycle, dan leave type tidak ditulis dengan variasi ejaan.
- [ ] Query active cycle memakai konfigurasi id 1.

Commit:

```bash
git add app/Models
git commit -m "refactor: rapikan model dan kontrak domain SCP"
```
