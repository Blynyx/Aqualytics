<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('fish_farm_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('role')->default('admin')->after('password');
        });

        Schema::table('ponds', function (Blueprint $table) {
            $table->foreignId('fish_farm_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });

        $now = now();

        DB::table('users')
            ->orderBy('id')
            ->get()
            ->each(function (object $user) use ($now): void {
                $fishFarmId = DB::table('fish_farms')->insertGetId([
                    'name' => "Piscigranja de {$user->name}",
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'fish_farm_id' => $fishFarmId,
                        'role' => 'admin',
                    ]);

                DB::table('ponds')
                    ->where('user_id', $user->id)
                    ->update([
                        'fish_farm_id' => $fishFarmId,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('ponds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fish_farm_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fish_farm_id');
            $table->dropColumn('role');
        });
    }
};
