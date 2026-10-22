<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('description');
            $table->text('admin_note')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('admin_note')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');

            $table->index(['status', 'created_at']);
        });

        // Existing live resources remain approved
        DB::table('resources')->update(['status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['status', 'admin_note', 'reviewed_by', 'reviewed_at']);
        });
    }
};
