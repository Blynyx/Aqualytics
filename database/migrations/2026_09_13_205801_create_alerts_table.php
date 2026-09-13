<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pond_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reading_id')->constrained()->cascadeOnDelete();
            $table->string('parameter');
            $table->decimal('value', 8, 2);
            $table->decimal('min_threshold', 8, 2)->nullable();
            $table->decimal('max_threshold', 8, 2)->nullable();
            $table->string('severity');
            $table->string('status')->default('active');
            $table->string('message');
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
