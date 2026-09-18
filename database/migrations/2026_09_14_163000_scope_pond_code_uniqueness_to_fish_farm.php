<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ponds', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['fish_farm_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('ponds', function (Blueprint $table) {
            $table->dropUnique(['fish_farm_id', 'code']);
            $table->unique('code');
        });
    }
};
