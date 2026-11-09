<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:sync-parents', function () {
    $this->info('Memulai sinkronisasi dan penyelarasan akun Orang Tua untuk seluruh data siswa...');

    $parentRole = \App\Models\Role::where('role_name', 'Orang Tua')->firstOrFail();
    $students = \App\Models\User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
        ->select('id', 'name', 'username', 'nis', 'phone', 'address', 'parent_id')
        ->get();

    if ($students->isEmpty()) {
        $this->info('Tidak ada data siswa ditemukan.');
        return 0;
    }

    $this->info("Ditemukan {$students->count()} data siswa. Memproses akun orang tua...");

    $bar = $this->output->createProgressBar($students->count());
    $bar->start();

    $created = 0;
    $synced = 0;
    $nisHashes = [];

    foreach ($students as $student) {
        $nis = preg_replace('/[^0-9A-Za-z]/', '', (string)($student->nis ?? ''));
        $baseParentUser = $nis ? "ortu_{$nis}" : "ortu_" . preg_replace('/[^0-9A-Za-z_]/', '', (string)$student->username);
        $parentPasswordPlain = $nis ? "p{$nis}" : "portu123";

        if (!isset($nisHashes[$parentPasswordPlain])) {
            $nisHashes[$parentPasswordPlain] = password_hash($parentPasswordPlain, PASSWORD_BCRYPT, ['cost' => 10]);
        }
        $hash = $nisHashes[$parentPasswordPlain];

        // Jika siswa sudah terhubung akun orang tua
        if ($student->parent_id) {
            $parent = \Illuminate\Support\Facades\DB::table('users')->where('id', $student->parent_id)->first();
            if ($parent) {
                $targetUsername = $parent->username;
                if ($targetUsername !== $baseParentUser) {
                    $orphan = \Illuminate\Support\Facades\DB::table('users')->where('username', $baseParentUser)->first();
                    if (!$orphan || $orphan->id === $parent->id) {
                        $targetUsername = $baseParentUser;
                    }
                }

                \Illuminate\Support\Facades\DB::table('users')->where('id', $parent->id)->update([
                    'username'   => $targetUsername,
                    'password'   => $hash,
                    'role_id'    => $parentRole->id,
                    'name'       => 'Orang tua ' . trim($student->name),
                    'updated_at' => now(),
                ]);
                $synced++;
                $bar->advance();
                continue;
            }
        }

        // Jika belum terhubung, cari akun eksisting atau buat baru
        $existing = \Illuminate\Support\Facades\DB::table('users')->where('username', $baseParentUser)->first();
        if ($existing) {
            \Illuminate\Support\Facades\DB::table('users')->where('id', $existing->id)->update([
                'password'   => $hash,
                'role_id'    => $parentRole->id,
                'name'       => 'Orang tua ' . trim($student->name),
                'updated_at' => now(),
            ]);
            \Illuminate\Support\Facades\DB::table('users')->where('id', $student->id)->update(['parent_id' => $existing->id]);
            $created++;
            $bar->advance();
            continue;
        }

        $uniqueParentUser = substr($baseParentUser, 0, 45);
        $pCounter = 1;
        while (\Illuminate\Support\Facades\DB::table('users')->where('username', $uniqueParentUser)->exists()) {
            $uniqueParentUser = substr($baseParentUser, 0, 38) . "_{$pCounter}";
            $pCounter++;
        }

        $parentId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
            'name'           => 'Orang tua ' . trim($student->name),
            'username'       => $uniqueParentUser,
            'password'       => $hash,
            'role_id'        => $parentRole->id,
            'phone'          => $student->phone,
            'address'        => $student->address,
            'student_status' => 'Aktif',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('users')->where('id', $student->id)->update(['parent_id' => $parentId]);
        $created++;
        $bar->advance();
    }

    $bar->finish();
    $this->newLine();
    $this->info("Sukses! {$created} akun baru dibuat, {$synced} akun orang tua diselaraskan sandinya (Password: p[NIS]).");
    return 0;
})->purpose('Sinkronisasi dan penyelarasan akun Orang Tua otomatis untuk semua data siswa');
