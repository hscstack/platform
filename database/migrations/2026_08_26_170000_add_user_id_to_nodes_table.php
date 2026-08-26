<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('parent_id')->constrained('users')->nullOnDelete();
        });

        // Assign existing folders to user ID 1 if user 1 exists
        if (DB::table('users')->where('id', 1)->exists()) {
            DB::table('nodes')->whereNull('user_id')->update(['user_id' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
