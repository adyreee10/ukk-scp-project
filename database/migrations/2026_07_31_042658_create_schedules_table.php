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
        ->restrictOnDelete();

    $table->foreignId('class_id')
        ->constrained('classes')
        ->cascadeOnUpdate()
        ->restrictOnDelete();

    $table->foreignId('room_id')
        ->constrained('rooms')
        ->cascadeOnUpdate()
        ->restrictOnDelete();

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

    $table->string('cycle_type', 20);

    $table->index(['cycle_type', 'day_of_week']);
    $table->index(['room_id', 'day_of_week', 'cycle_type']);
    $table->index(['class_id', 'day_of_week', 'cycle_type']);
    $table->index(['teacher_id', 'day_of_week', 'cycle_type']);

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
