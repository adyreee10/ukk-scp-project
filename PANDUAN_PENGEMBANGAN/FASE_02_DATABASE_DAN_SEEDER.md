# Fase 02 — Database, Constraint, dan Seeder

## Tujuan

Membuat database menjadi sumber kebenaran yang mencegah duplikasi dan menyediakan semua data dasar. Karena proyek masih tahap pengembangan, jalur paling bersih adalah membetulkan migration awal lalu melakukan fresh migration **olehmu sendiri** setelah backup data.

## Sebelum mengubah

Jika database berisi data penting, ekspor/backup terlebih dahulu. `migrate:fresh` menghapus semua tabel. Jangan jalankan perintah itu pada database produksi atau data yang ingin dipertahankan.

## 1. Samakan tabel kelas dengan blueprint

Di `2026_07_31_042634_create_school_classes_table.php`, ganti `school_classes` menjadi `classes` pada `up()` dan `down()`:

```php
Schema::create('classes', function (Blueprint $table) {
    $table->id();
    $table->string('class_name', 50)->unique();
    $table->timestamps();
});

// down()
Schema::dropIfExists('classes');
```

Di seluruh migration foreign key kelas, gunakan:

```php
->constrained('classes')
```

Ini berlaku pada `users.class_id`, `users.homeroom_class_id`, dan `schedules.class_id`.

## 2. Perbaiki kolom user

Ganti isi callback `Schema::table('users', ...)` pada migration `2026_08_01_153647_add_role_class_parent_to_users_table.php` menjadi:

```php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('role_id')
        ->after('id')
        ->constrained('roles')
        ->cascadeOnUpdate()
        ->restrictOnDelete();

    $table->foreignId('class_id')
        ->nullable()
        ->after('role_id')
        ->constrained('classes')
        ->cascadeOnUpdate()
        ->nullOnDelete();

    $table->foreignId('homeroom_class_id')
        ->nullable()
        ->after('class_id')
        ->constrained('classes')
        ->cascadeOnUpdate()
        ->nullOnDelete();

    $table->foreignId('parent_id')
        ->nullable()
        ->after('homeroom_class_id')
        ->constrained('users')
        ->cascadeOnUpdate()
        ->nullOnDelete();

    $table->string('username', 50)
        ->unique()
        ->after('name');
});
```

Dan `down()`:

```php
Schema::table('users', function (Blueprint $table) {
    $table->dropConstrainedForeignId('role_id');
    $table->dropConstrainedForeignId('class_id');
    $table->dropConstrainedForeignId('homeroom_class_id');
    $table->dropConstrainedForeignId('parent_id');
    $table->dropColumn('username');
});
```

`role_id` dan `username` tidak nullable karena user tanpa role/username tidak valid untuk sistem ini.

## 3. Konsistenkan nilai cycle

Di migration schedule, gunakan string agar mudah divalidasi lintas PostgreSQL/MySQL/SQLite:

```php
$table->string('cycle_type', 20);
```

Tambahkan validasi integritas dasar setelah kolom waktu:

```php
$table->index(['cycle_type', 'day_of_week']);
$table->index(['room_id', 'day_of_week', 'cycle_type']);
$table->index(['class_id', 'day_of_week', 'cycle_type']);
$table->index(['teacher_id', 'day_of_week', 'cycle_type']);
```

Conflict overlap tidak dapat dijamin dengan unique constraint biasa, sehingga tetap wajib dicek service pada Fase 06.

## 4. Jadikan jurnal dan presensi unik

Pada migration journals, buat foto wajib dan tambahkan unique constraint:

```php
$table->string('photo_path', 255);
$table->unique(['schedule_id', 'date']);
```

Pada migration attendances:

```php
$table->string('status', 20);
$table->unique(['journal_id', 'student_id']);
$table->index(['student_id', 'date']);
```

## 5. Tambahkan jenis izin eksplisit

Pada migration leave requests, tambahkan setelah `end_date`:

```php
$table->string('leave_type', 10);
```

Ganti enum approval menjadi string dan tambah index:

```php
$table->string('status_approval', 20)->default('Pending');
$table->index(['student_id', 'status_approval', 'start_date', 'end_date']);
```

## 6. Tetapkan satu baris konfigurasi global

Migration `system_configurations` sebaiknya berisi:

```php
Schema::create('system_configurations', function (Blueprint $table) {
    $table->id();
    $table->string('active_cycle', 20)->default('Putaran 1');
    $table->boolean('is_schedule_locked')->default(false);
    $table->foreignId('updated_by')
        ->constrained('users')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
    $table->timestamps();
});
```

Aplikasi selalu memakai row `id = 1`. Jangan membuat row konfigurasi baru pada setiap update.

## 7. Seeder final yang deterministik

Ganti `database/seeders/DatabaseSeeder.php` dengan kode berikut. Seeder individual lama boleh tetap ada, tetapi jangan lagi dipanggil dari seeder utama.

```php
<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Waka Kurikulum',
            'Guru',
            'Wali Kelas',
            'Guru BK',
            'Siswa',
            'Orang Tua',
        ] as $roleName) {
            Role::updateOrCreate(['role_name' => $roleName]);
        }

        foreach (['X RPL 1', 'X RPL 2', 'XI RPL 1', 'XI RPL 2', 'XII RPL 1', 'XII RPL 2'] as $name) {
            SchoolClass::updateOrCreate(['class_name' => $name]);
        }

        foreach (['Ruang 101', 'Ruang 102', 'Lab RPL', 'Lab TKJ', 'Lab Multimedia'] as $name) {
            Room::updateOrCreate(['room_name' => $name]);
        }

        $class = SchoolClass::where('class_name', 'X RPL 1')->firstOrFail();

        $waka = $this->user('waka', [
            'role_id' => $this->role('Waka Kurikulum'),
            'name' => 'Waka Kurikulum',
            'email' => 'waka@sekolah.test',
            'password' => Hash::make('password123'),
        ]);

        $this->user('guru', [
            'role_id' => $this->role('Guru'),
            'name' => 'Guru Demo',
            'email' => 'guru@sekolah.test',
            'password' => Hash::make('password123'),
        ]);

        $this->user('walikelas', [
            'role_id' => $this->role('Wali Kelas'),
            'homeroom_class_id' => $class->id,
            'name' => 'Wali Kelas Demo',
            'email' => 'walikelas@sekolah.test',
            'password' => Hash::make('password123'),
        ]);

        $this->user('gurubk', [
            'role_id' => $this->role('Guru BK'),
            'name' => 'Guru BK Demo',
            'email' => 'gurubk@sekolah.test',
            'password' => Hash::make('password123'),
        ]);

        $parent = $this->user('orangtua', [
            'role_id' => $this->role('Orang Tua'),
            'name' => 'Orang Tua Demo',
            'email' => 'orangtua@sekolah.test',
            'password' => Hash::make('password123'),
        ]);

        $this->user('siswa', [
            'role_id' => $this->role('Siswa'),
            'class_id' => $class->id,
            'parent_id' => $parent->id,
            'name' => 'Siswa Demo',
            'email' => 'siswa@sekolah.test',
            'password' => Hash::make('password123'),
        ]);

        SystemConfiguration::updateOrCreate(
            ['id' => 1],
            [
                'active_cycle' => 'Putaran 1',
                'is_schedule_locked' => false,
                'updated_by' => $waka->id,
            ],
        );
    }

    private function role(string $name): int
    {
        return Role::where('role_name', $name)->firstOrFail()->id;
    }

    private function user(string $username, array $attributes): User
    {
        return User::updateOrCreate(['username' => $username], $attributes);
    }
}
```

## 8. Model tabel classes

Pada `SchoolClass` tambahkan:

```php
protected $table = 'classes';
```

## Perintah verifikasi yang kamu jalankan sendiri

Hanya setelah backup dan setelah seluruh edit Fase 02 selesai:

```bash
php artisan migrate:fresh --seed
php artisan migrate:status
```

## Gerbang selesai

- [ ] Ada tepat enam role.
- [ ] Ada satu konfigurasi global id 1.
- [ ] Semua user demo memiliki password ter-hash.
- [ ] Jurnal ganda untuk schedule/tanggal ditolak database.
- [ ] Presensi ganda untuk journal/siswa ditolak database.
- [ ] `leave_type` tersedia.
- [ ] Nama cycle dan approval sama dengan Fase 01.
- [ ] Fresh migration dan seed berhasil saat kamu jalankan.

Commit:

```bash
git add database app/Models/SchoolClass.php
git commit -m "fix: selaraskan schema dan data awal SCP"
```

