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
        Schema::rename('chat_reports', 'reports');

        Schema::table('reports', function (Blueprint $table) {
            $table->string('reportable_type')->nullable()->after('reported_user_username');
            $table->unsignedBigInteger('reportable_id')->nullable()->after('reportable_type');
            $table->renameColumn('message_content', 'content_snapshot');
            $table->index(['reportable_type', 'reportable_id']);
        });

        DB::table('reports')->whereNull('reportable_type')->update([
            'reportable_type' => 'App\Models\ChatMessage',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['reportable_type', 'reportable_id']);
            $table->dropColumn(['reportable_type', 'reportable_id']);
            $table->renameColumn('content_snapshot', 'message_content');
        });

        Schema::rename('reports', 'chat_reports');
    }
};
