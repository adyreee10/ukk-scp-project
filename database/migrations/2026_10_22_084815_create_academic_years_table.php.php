<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('year', 20)->unique();
            $table->string('status', 30)->default('Tahun ajaran mendatang');
            $table->timestamps();
        });

        // Seed data awal
        DB::table('academic_years')->insert([
            ['year' => '2024/2025', 'status' => 'Tahun ajaran selesai', 'created_at' => now(), 'updated_at' => now()],
            ['year' => '2025/2026', 'status' => 'Tahun ajaran selesai', 'created_at' => now(), 'updated_at' => now()],
            ['year' => '2026/2027', 'status' => 'Tahun ajaran berjalan', 'created_at' => now(), 'updated_at' => now()],
            ['year' => '2027/2028', 'status' => 'Tahun ajaran mendatang', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
