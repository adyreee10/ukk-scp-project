<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Buat ulang tabel dengan dukungan per hari
        Schema::dropIfExists('lesson_hours');
        Schema::create('lesson_hours', function (Blueprint $table) {
            $table->id();
            $table->string('day_of_week', 20); // 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'
            $table->unsignedTinyInteger('session_number'); // 1 sampai 10
            $table->string('name', 50); // "Jam ke - 1"
            $table->string('start_time', 10);
            $table->string('end_time', 10);
            $table->unsignedSmallInteger('duration_minutes')->default(40);
            $table->string('notes', 100)->nullable();
            $table->timestamps();

            $table->unique(['day_of_week', 'session_number']);
        });

        // 1. DATA RABU & KAMIS (Normal Mulai 07:15, Default 40 Menit)
        $normalSlots = [
            1  => ['07:15', '07:55', 40, 'Sesi Belajar Pagi'],
            2  => ['07:55', '08:35', 40, 'Sesi Belajar Pagi'],
            3  => ['08:35', '09:15', 40, 'Menjelang Istirahat 1 (09:15 - 09:30)'],
            4  => ['09:30', '10:10', 40, 'Sesi Setelah Istirahat 1'],
            5  => ['10:10', '10:50', 40, 'Sesi Belajar Siang'],
            6  => ['10:50', '11:30', 40, 'Menjelang Istirahat 2 (11:30 - 12:30 Ishoma)'],
            7  => ['12:30', '13:10', 40, 'Sesi Setelah Ishoma'],
            8  => ['13:10', '13:50', 40, 'Sesi Belajar Siang'],
            9  => ['13:50', '14:30', 40, 'Sesi Sore'],
            10 => ['14:30', '15:10', 40, 'Sesi Terakhir (15:10 - 15:30 Ashar & Piket)'],
        ];

        // 2. DATA SENIN (Upacara 07:00 - 08:00, Mulai Belajar 08:00)
        $mondaySlots = [
            1  => ['08:00', '08:35', 35, 'Setelah Upacara (07:00 - 08:00)'],
            2  => ['08:35', '09:10', 35, 'Sesi Belajar Pagi'],
            3  => ['09:10', '09:45', 35, 'Menjelang Istirahat 1 (09:45 - 10:00)'],
            4  => ['10:00', '10:35', 35, 'Sesi Setelah Istirahat 1'],
            5  => ['10:35', '11:10', 35, 'Sesi Belajar Siang'],
            6  => ['11:10', '11:45', 35, 'Menjelang Istirahat 2 (11:45 - 12:45 Ishoma)'],
            7  => ['12:45', '13:20', 35, 'Sesi Setelah Ishoma'],
            8  => ['13:20', '13:55', 35, 'Sesi Belajar Sore'],
            9  => ['13:55', '14:30', 35, 'Sesi Belajar Sore'],
            10 => ['14:30', '15:10', 40, 'Sesi Terakhir (15:10 - 15:30 Ashar & Piket)'],
        ];

        // 3. DATA SELASA (Literasi 07:00 - 07:30, Mulai Belajar 07:30, 40 Menit)
        $tuesdaySlots = [
            1  => ['07:30', '08:10', 40, 'Setelah Literasi (07:00 - 07:30)'],
            2  => ['08:10', '08:50', 40, 'Sesi Belajar Pagi'],
            3  => ['08:50', '09:30', 40, 'Menjelang Istirahat 1 (09:30 - 09:45)'],
            4  => ['09:45', '10:25', 40, 'Sesi Setelah Istirahat 1'],
            5  => ['10:25', '11:05', 40, 'Sesi Belajar Siang'],
            6  => ['11:05', '11:45', 40, 'Menjelang Istirahat 2 (11:45 - 12:45 Ishoma)'],
            7  => ['12:45', '13:25', 40, 'Sesi Setelah Ishoma'],
            8  => ['13:25', '14:05', 40, 'Sesi Belajar Sore'],
            9  => ['14:05', '14:45', 40, 'Sesi Sore'],
            10 => ['14:45', '15:10', 25, 'Sesi Terakhir (15:10 - 15:30 Ashar & Piket)'],
        ];

        // 4. DATA JUMAT (Mulai 07:15, Shalat Jumat 11:25 - 12:40, Maksimal Jam ke-8)
        $fridaySlots = [
            1 => ['07:15', '07:55', 40, 'Sesi Belajar Pagi'],
            2 => ['07:55', '08:35', 40, 'Sesi Belajar Pagi'],
            3 => ['08:35', '09:15', 40, 'Menjelang Istirahat 1 (09:15 - 09:30)'],
            4 => ['09:30', '10:10', 40, 'Sesi Setelah Istirahat 1'],
            5 => ['10:10', '10:50', 40, 'Sesi Menjelang Jumat'],
            6 => ['10:50', '11:25', 35, 'Menjelang Shalat Jumat (11:25 - 12:40)'],
            7 => ['12:40', '13:15', 35, 'Sesi Setelah Shalat Jumat'],
            8 => ['13:15', '13:50', 35, 'Sesi Terakhir Jumat (13:50 - 14:00 Piket)'],
        ];

        $allData = [
            'Senin'  => $mondaySlots,
            'Selasa' => $tuesdaySlots,
            'Rabu'   => $normalSlots,
            'Kamis'  => $normalSlots,
            'Jumat'  => $fridaySlots,
        ];

        foreach ($allData as $day => $slots) {
            foreach ($slots as $num => $s) {
                DB::table('lesson_hours')->insert([
                    'day_of_week'      => $day,
                    'session_number'   => $num,
                    'name'             => "Jam ke - {$num}",
                    'start_time'       => $s[0],
                    'end_time'         => $s[1],
                    'duration_minutes' => $s[2],
                    'notes'            => $s[3],
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_hours');
    }
};
