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
        Schema::create('leave_requests', function (Blueprint $table) {
    $table->id();

    $table->foreignId('student_id')
        ->constrained('users')
        ->cascadeOnUpdate()
        ->cascadeOnDelete();

    $table->foreignId('applicant_id')
        ->constrained('users')
        ->cascadeOnUpdate()
        ->cascadeOnDelete();

    $table->foreignId('verified_by')
        ->nullable()
        ->constrained('users')
        ->cascadeOnUpdate()
        ->nullOnDelete();

    $table->date('start_date');

    $table->date('end_date');

    $table->text('reason');

    $table->string('document_path');

    $table->enum('status_approval',[
        'Pending',
        'Disetujui',
        'Ditolak'
    ]);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
