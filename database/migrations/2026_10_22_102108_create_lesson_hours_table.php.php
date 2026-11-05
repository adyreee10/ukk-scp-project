<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('session_number')->unique(); // 1 sampai 10
            $table->string('name', 50); // "Jam ke - 1"
            $table->string('start_time', 10); // "07:00"
            $table->string('end_time', 10); // "07:40"
            $table->unsignedSmallInteger('duration_minutes')->default(40); // 40 menit (default baru)
            $table->string('notes', 100)->nullable();
            $table->timestamps();
        });

        // Seed data awal: Default 40 menit per sesi belajar
        $defaultHours = [
            ['session_number' => 1,  'name' => 'Jam ke - 1',  'start_time' => '07:00', 'end_time' => '07:40', 'duration_minutes' => 40, 'notes' => 'Sesi Belajar Pagi'],
            ['session_number' => 2,  'name' => 'Jam ke - 2',  'start_time' => '07:40', 'end_time' => '08:20', 'duration_minutes' => 40, 'notes' => 'Sesi Belajar Pagi'],
            ['session_number' => 3,  'name' => 'Jam ke - 3',  'start_time' => '08:20', 'end_time' => '09:00', 'duration_minutes' => 40, 'notes' => 'Sesi Belajar Pagi'],
            ['session_number' => 4,  'name' => 'Jam ke - 4',  'start_time' => '09:00', 'end_time' => '09:40', 'duration_minutes' => 40, 'notes' => 'Menjelang Istirahat 1'],
            ['session_number' => 5,  'name' => 'Jam ke - 5',  'start_time' => '10:00', 'end_time' => '10:40', 'duration_minutes' => 40, 'notes' => 'Setelah Istirahat 1 (09:40 - 10:00)'],
            ['session_number' => 6,  'name' => 'Jam ke - 6',  'start_time' => '10:40', 'end_time' => '11:20', 'duration_minutes' => 40, 'notes' => 'Sesi Siang'],
            ['session_number' => 7,  'name' => 'Jam ke - 7',  'start_time' => '11:20', 'end_time' => '12:00', 'duration_minutes' => 40, 'notes' => 'Menjelang Istirahat 2 (Dhuhur)'],
            ['session_number' => 8,  'name' => 'Jam ke - 8',  'start_time' => '12:30', 'end_time' => '13:10', 'duration_minutes' => 40, 'notes' => 'Setelah Dhuhur (Batas Maksimal Jumat)'],
            ['session_number' => 9,  'name' => 'Jam ke - 9',  'start_time' => '13:10', 'end_time' => '13:50', 'duration_minutes' => 40, 'notes' => 'Sesi Sore (Senin - Kamis)'],
            ['session_number' => 10, 'name' => 'Jam ke - 10', 'start_time' => '13:50', 'end_time' => '14:30', 'duration_minutes' => 40, 'notes' => 'Sesi Terakhir (Senin - Kamis)'],
        ];

        foreach ($defaultHours as $h) {
            DB::table('lesson_hours')->insert(array_merge($h, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_hours');
    }
};
