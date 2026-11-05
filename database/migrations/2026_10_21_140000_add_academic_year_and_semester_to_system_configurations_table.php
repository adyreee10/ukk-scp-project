<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->string('academic_year', 20)->default('2025/2026')->after('active_cycle');
            $table->string('semester', 30)->default('Semester 2 (Genap)')->after('academic_year');
        });
    }

    public function down(): void
    {
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->dropColumn(['academic_year', 'semester']);
        });
    }
};
