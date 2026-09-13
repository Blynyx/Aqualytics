<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->foreignId('reported_by_user_id')
                ->nullable()
                ->after('reading_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->after('reported_by_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('resolved_by_user_id')
                ->nullable()
                ->after('assigned_to_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('detected_at');
            $table->text('resolution_notes')->nullable()->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reported_by_user_id');
            $table->dropConstrainedForeignId('assigned_to_user_id');
            $table->dropConstrainedForeignId('resolved_by_user_id');
            $table->dropColumn([
                'assigned_at',
                'resolution_notes',
            ]);
        });
    }
};
