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
            Schema::create('schedules', function (Blueprint $table) {
    $table->id();

    $table->foreignId('teacher_id')
        ->constrained('users')
        ->cascadeOnUpdate()
        ->cascadeOnDelete();

    $table->foreignId('class_id')
        ->constrained('school_classes')
        ->cascadeOnUpdate()
        ->cascadeOnDelete();

    $table->foreignId('room_id')
        ->constrained('rooms')
        ->cascadeOnUpdate()
        ->cascadeOnDelete();

    $table->string('subject',100);

    $table->enum('day_of_week',[
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu'
    ]);

    $table->time('start_time');

    $table->time('end_time');

    $table->enum('cycle_type',[
        'ganjil',
        'genap'
    ]);

    $table->timestamps();
});
        }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
