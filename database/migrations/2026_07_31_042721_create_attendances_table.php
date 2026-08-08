<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
    $table->id();

    $table->foreignId('journal_id')
        ->constrained()
        ->cascadeOnUpdate()
        ->cascadeOnDelete();

    $table->foreignId('student_id')
        ->constrained('users')
        ->cascadeOnUpdate()
        ->cascadeOnDelete();

    $table->enum('status',[
        'Hadir',
        'Izin',
        'Sakit',
        'Alpa'
    ]);

    $table->date('date');

    $table->timestamps();
});
        }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
