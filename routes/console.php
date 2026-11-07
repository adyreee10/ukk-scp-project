<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:sync-parents', function () {
    $this->info('Memulai sinkronisasi akun Orang Tua untuk seluruh data siswa...');

    $parentRole = \App\Models\Role::where('role_name', 'Orang Tua')->firstOrFail();
    $unlinkedStudents = \App\Models\User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
        ->whereNull('parent_id')
        ->get();

    if ($unlinkedStudents->isEmpty()) {
        $this->info('Semua siswa sudah memiliki akun orang tua yang terhubung.');
        return 0;
    }

    $this->info("Ditemukan {$unlinkedStudents->count()} siswa belum memiliki akun orang tua. Memproses...");

    $bar = $this->output->createProgressBar($unlinkedStudents->count());
    $bar->start();

    $created = 0;
    foreach ($unlinkedStudents as $student) {
        $nis = trim((string)($student->nis ?? ''));
        $baseParentUser = $nis ? "ortu_{$nis}" : "ortu_" . $student->username;
        $uniqueParentUser = substr($baseParentUser, 0, 45);
        $pCounter = 1;
        while (\App\Models\User::where('username', $uniqueParentUser)->exists()) {
            $uniqueParentUser = substr($baseParentUser, 0, 38) . "_{$pCounter}";
            $pCounter++;
        }
        $parentPasswordPlain = $nis ? "p{$nis}" : "portu123";

        $parent = \App\Models\User::create([
            'name'           => 'Orang tua ' . trim($student->name),
            'username'       => $uniqueParentUser,
            'password'       => \Illuminate\Support\Facades\Hash::make($parentPasswordPlain),
            'role_id'        => $parentRole->id,
            'phone'          => $student->phone,
            'address'        => $student->address,
            'student_status' => 'Aktif',
        ]);

        $student->update(['parent_id' => $parent->id]);
        $created++;
        $bar->advance();
    }

    $bar->finish();
    $this->newLine();
    $this->info("Sukses! Berhasil membuat {$created} akun orang tua baru (Password: p[NIS]).");
    return 0;
})->purpose('Sinkronisasi akun Orang Tua otomatis untuk semua siswa yang belum memiliki akun');
