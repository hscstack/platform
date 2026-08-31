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
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->string('moderation_status')->default('approved')->index()->after('is_locked');
        });

        if (Schema::hasColumn('forum_posts', 'is_published')) {
            DB::table('forum_posts')
                ->where('is_published', false)
                ->update(['moderation_status' => 'rejected']);

            Schema::table('forum_posts', function (Blueprint $table) {
                $table->dropIndex(['is_published']);
                $table->dropColumn('is_published');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->index()->after('is_locked');
        });

        DB::table('forum_posts')
            ->whereIn('moderation_status', ['pending', 'flagged', 'rejected'])
            ->update(['is_published' => false]);

        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropIndex(['moderation_status']);
            $table->dropColumn('moderation_status');
        });
    }
};
