<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // RPL, TKJ, DKV, MP, AKL, LPS, BR, BD
            $table->string('name', 100);
            $table->integer('rombel_x_xi')->default(2);
            $table->integer('rombel_xii')->default(2);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('majors');
    }
};
