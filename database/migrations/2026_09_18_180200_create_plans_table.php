<?php

use Database\Seeders\PlanSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('account_type');
            $table->decimal('monthly_price', 8, 2)->default(0);
            $table->unsignedInteger('max_units');
            $table->unsignedInteger('max_users');
            $table->unsignedInteger('max_devices');
            $table->unsignedInteger('history_days');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        (new PlanSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
