<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\LeaveRequest;
use App\Models\Major;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Inisialisasi Seluruh Role Pengguna
        $roles = [
            'Waka Kurikulum',
            'Guru',
            'Wali Kelas',
            'Guru BK',
            'Siswa',
            'Orang Tua',
        ];
        foreach ($roles as $roleName) {
            Role::updateOrCreate(['role_name' => $roleName]);
        }

        // 2. Data Master Jurusan (Majors)
        $majors = [
            ['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak', 'rombel_x' => 2, 'rombel_xi' => 2, 'rombel_xii' => 2],
            ['code' => 'TKJ', 'name' => 'Teknik Komputer dan Jaringan', 'rombel_x' => 2, 'rombel_xi' => 2, 'rombel_xii' => 2],
            ['code' => 'AK', 'name' => 'Akuntansi dan Keuangan Lembaga', 'rombel_x' => 2, 'rombel_xi' => 2, 'rombel_xii' => 2],
        ];
        foreach ($majors as $m) {
            Major::updateOrCreate(['code' => $m['code']], $m);
        }

        // 3. Data Master Tahun Ajaran (Academic Year)
        AcademicYear::updateOrCreate(
            ['year' => '2026/2027'],
            ['status' => 'Aktif']
        );

        // 4. Data Master Rombel / Kelas (School Classes)
        $classes = [
            'X RPL 1',
            'X RPL 2',
            'XI RPL 1',
            'XI RPL 2',
            'XII RPL 1',
            'XII RPL 2',
        ];
        foreach ($classes as $name) {
            SchoolClass::updateOrCreate(['class_name' => $name]);
        }

        // 5. Data Master Ruang Kelas & Laboratorium (Rooms)
        $rooms = [
            'Ruang 101',
            'Ruang 102',
            'Lab RPL',
            'Lab TKJ',
            'Lab Multimedia',
        ];
        foreach ($rooms as $name) {
            Room::updateOrCreate(['room_name' => $name]);
        }

        $classXRpl1   = SchoolClass::where('class_name', 'X RPL 1')->first();
        $classXiiRpl2 = SchoolClass::where('class_name', 'XII RPL 2')->first();
        $roomLabRpl   = Room::where('room_name', 'Lab RPL')->first();

        // 6. Akun Demo Utama (Identik dengan Kredensial di README.md)
        // A. Waka Kurikulum
        $waka = $this->upsertUser('waka', [
            'role_id'  => $this->role('Waka Kurikulum'),
            'name'     => 'Waka Kurikulum',
            'nip'      => 'waka',
            'email'    => 'waka@sekolah.test',
            'password' => Hash::make('password123'),
        ]);

        // B. Wali Kelas (Bu Rosyida) - Kelas XII RPL 2
        $guruRosyida = $this->upsertUser('guru_rosyida', [
            'role_id'           => $this->role('Wali Kelas'),
            'homeroom_class_id' => $classXiiRpl2?->id,
            'name'              => 'ROSYIDA RAMADHANI, S.Pd',
            'nip'               => '199203252014022002',
            'email'             => 'guru_rosyida@guru.smk.sch.id',
            'password'          => Hash::make('password123'),
        ], '199203252014022002');

        // C. Guru Pengajar (Bu Dinda)
        $guruDinda = $this->upsertUser('guru_dinda', [
            'role_id'  => $this->role('Guru'),
            'name'     => 'DINDA AYU DEWANTI, S.Kom',
            'nip'      => '3403036308970002',
            'email'    => 'guru_dinda@guru.smk.sch.id',
            'password' => Hash::make('password123'),
        ], '3403036308970002');

        // D. Guru Pengajar (Bu Mulati)
        $guruMulati = $this->upsertUser('guru_mulati', [
            'role_id'  => $this->role('Guru'),
            'name'     => 'MULATI, S.Pd.',
            'nip'      => '196709222007012012',
            'email'    => 'guru_mulati@guru.smk.sch.id',
            'password' => Hash::make('password123'),
        ], '196709222007012012');

        // E. Guru BK (Bu Ryawidyaningtyas)
        $guruBk = $this->upsertUser('guru_ryawidyaningtyas', [
            'role_id'  => $this->role('Guru BK'),
            'name'     => 'RYAWIDYANINGTYAS, S.Pd.',
            'nip'      => '199503272022212001',
            'email'    => 'guru_ryawidyaningtyas@guru.smk.sch.id',
            'password' => Hash::make('password123'),
        ], '199503272022212001');

        // Tautkan seluruh kelas binaan ke Guru BK
        $allClassIds = SchoolClass::pluck('id')->toArray();
        if ($guruBk && !empty($allClassIds)) {
            $guruBk->counselorClasses()->sync($allClassIds);
        }

        // F. Orang Tua Demo (Orang tua Rezky Aditya)
        $parentDemo = $this->upsertUser('ortu_17821', [
            'role_id'  => $this->role('Orang Tua'),
            'name'     => 'Orang tua REZKY ADITYA',
            'email'    => 'ortu_17821@wali.smk.sch.id',
            'password' => Hash::make('p17821'),
        ]);

        // G. Siswa Demo (REZKY ADITYA - 17821)
        $siswaDemo = $this->upsertUser('17821', [
            'role_id'        => $this->role('Siswa'),
            'class_id'       => $classXiiRpl2?->id,
            'parent_id'      => $parentDemo->id,
            'name'           => 'REZKY ADITYA',
            'nis'            => '17821',
            'student_status' => 'Aktif',
            'email'          => 'adityarizkyy230172@gmail.com',
            'password'       => Hash::make('password123'),
        ], null, '17821');

        // Pastikan relasi Orang Tua -> Siswa sinkron dua arah
        if ($parentDemo && $siswaDemo) {
            $siswaDemo->update(['parent_id' => $parentDemo->id]);
        }

        // 7. Akun Cadangan Standar Generic (Dual-Compatibility)
        $this->upsertUser('guru', [
            'role_id'  => $this->role('Guru'),
            'name'     => 'Guru Demo',
            'nip'      => 'guru',
            'email'    => 'guru@sekolah.test',
            'password' => Hash::make('password123'),
        ]);

        $this->upsertUser('walikelas', [
            'role_id'           => $this->role('Wali Kelas'),
            'homeroom_class_id' => $classXRpl1?->id,
            'name'              => 'Wali Kelas Demo',
            'nip'               => 'walikelas',
            'email'             => 'walikelas@sekolah.test',
            'password'          => Hash::make('password123'),
        ]);

        $genericBk = $this->upsertUser('gurubk', [
            'role_id'  => $this->role('Guru BK'),
            'name'     => 'Guru BK Demo',
            'nip'      => 'gurubk',
            'email'    => 'gurubk@sekolah.test',
            'password' => Hash::make('password123'),
        ]);
        if ($genericBk && !empty($allClassIds)) {
            $genericBk->counselorClasses()->sync($allClassIds);
        }

        $parentGeneric = $this->upsertUser('orangtua', [
            'role_id'  => $this->role('Orang Tua'),
            'name'     => 'Orang Tua Demo',
            'email'    => 'orangtua@sekolah.test',
            'password' => Hash::make('portu123'),
        ]);

        $this->upsertUser('siswa', [
            'role_id'        => $this->role('Siswa'),
            'class_id'       => $classXRpl1?->id,
            'parent_id'      => $parentGeneric->id,
            'name'           => 'Siswa Demo',
            'nis'            => '12345',
            'student_status' => 'Aktif',
            'email'          => 'siswa@sekolah.test',
            'password'       => Hash::make('password123'),
        ]);

        // 8. Konfigurasi Sistem Global (System Configuration)
        SystemConfiguration::updateOrCreate(
            ['id' => 1],
            [
                'active_cycle'       => 'Putaran 1',
                'academic_year'      => '2026/2027',
                'semester'           => 'Ganjil',
                'is_schedule_locked' => false,
                'is_cycle_1_locked'  => false,
                'is_cycle_2_locked'  => false,
                'updated_by'         => $waka->id,
            ]
        );

        // 9. Jadwal KBM Demo (Untuk Kemudahan Testing Mengajar Guru & Siswa)
        if ($roomLabRpl && $classXiiRpl2 && $guruDinda) {
            Schedule::updateOrCreate(
                [
                    'teacher_id' => $guruDinda->id,
                    'class_id'   => $classXiiRpl2->id,
                    'subject'    => 'Pemrograman Web dan Perangkat Bergerak',
                    'day_of_week'=> 'Senin',
                    'cycle_type' => 'Putaran 1',
                ],
                [
                    'room_id'    => $roomLabRpl->id,
                    'start_time' => '07:00:00',
                    'end_time'   => '10:00:00',
                ]
            );
        }

        if ($roomLabRpl && $classXiiRpl2 && $guruMulati) {
            Schedule::updateOrCreate(
                [
                    'teacher_id' => $guruMulati->id,
                    'class_id'   => $classXiiRpl2->id,
                    'subject'    => 'Basis Data',
                    'day_of_week'=> 'Selasa',
                    'cycle_type' => 'Putaran 1',
                ],
                [
                    'room_id'    => $roomLabRpl->id,
                    'start_time' => '07:00:00',
                    'end_time'   => '09:00:00',
                ]
            );
        }

        if ($roomLabRpl && $classXiiRpl2 && $guruRosyida) {
            Schedule::updateOrCreate(
                [
                    'teacher_id' => $guruRosyida->id,
                    'class_id'   => $classXiiRpl2->id,
                    'subject'    => 'Pemrograman Berorientasi Objek',
                    'day_of_week'=> 'Rabu',
                    'cycle_type' => 'Putaran 1',
                ],
                [
                    'room_id'    => $roomLabRpl->id,
                    'start_time' => '07:00:00',
                    'end_time'   => '10:00:00',
                ]
            );
        }

        // 10. Data Riwayat Permohonan Izin Demo (Untuk Testing Wali Kelas & BK)
        if ($siswaDemo && $parentDemo && $guruRosyida) {
            LeaveRequest::updateOrCreate(
                [
                    'student_id' => $siswaDemo->id,
                    'start_date' => now()->subDay()->toDateString(),
                ],
                [
                    'applicant_id'    => $parentDemo->id,
                    'verified_by'     => $guruRosyida->id,
                    'end_date'        => now()->toDateString(),
                    'leave_type'      => 'Izin',
                    'reason'          => 'Keperluan urusan keluarga mendesak',
                    'document_path'   => 'documents/surat_izin_demo.pdf',
                    'status_approval' => 'Approved',
                ]
            );
        }
    }

    private function role(string $name): int
    {
        return Role::where('role_name', $name)->firstOrFail()->id;
    }

    /**
     * Helper untuk upsert user dengan pencarian fleksibel (username, nip, atau nis)
     */
    private function upsertUser(string $username, array $attributes, ?string $nip = null, ?string $nis = null): User
    {
        $query = User::query();
        if ($nip) {
            $query->where('nip', $nip);
        } elseif ($nis) {
            $query->where('nis', $nis);
        } else {
            $query->where('username', $username);
        }

        $user = $query->first();

        if ($user) {
            $user->update($attributes);
            return $user;
        }

        return User::create(array_merge(['username' => $username], $attributes));
    }
}
