<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $farmPlan = DB::table('plans')->where('code', 'farm')->first();

        if ($farmPlan === null) {
            return;
        }

        $now = now();

        DB::table('fish_farms')
            ->orderBy('id')
            ->get()
            ->each(function (object $fishFarm) use ($farmPlan, $now): void {
                $exists = DB::table('subscriptions')
                    ->where('fish_farm_id', $fishFarm->id)
                    ->exists();

                if ($exists) {
                    return;
                }

                DB::table('subscriptions')->insert([
                    'fish_farm_id' => $fishFarm->id,
                    'plan_id' => $farmPlan->id,
                    'status' => 'active',
                    'starts_at' => $now,
                    'ends_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        // Existing production subscriptions are kept; schema rollback drops the table.
    }
};
