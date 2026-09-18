<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fish_farms', function (Blueprint $table) {
            $table->string('account_type')->default('farm')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('fish_farms', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });
    }
};
