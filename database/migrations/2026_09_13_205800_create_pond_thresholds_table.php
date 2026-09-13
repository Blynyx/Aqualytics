<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pond_thresholds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pond_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('temperature_min', 5, 2)->nullable();
            $table->decimal('temperature_max', 5, 2)->nullable();
            $table->decimal('ph_min', 4, 2)->nullable();
            $table->decimal('ph_max', 4, 2)->nullable();
            $table->decimal('turbidity_max', 8, 2)->nullable();
            $table->decimal('water_level_min', 8, 2)->nullable();
            $table->decimal('water_level_max', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pond_thresholds');
    }
};
