<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 16 hours limit in seconds (16 * 3600 = 57,600)
        DB::table('daily_study_logs')
            ->where('total_seconds', '>', 57600)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Deleted invalid excessive logs cannot be reconstructed
    }
};
