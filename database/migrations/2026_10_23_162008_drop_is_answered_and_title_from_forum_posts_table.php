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
        // Backfill body with title for posts that have empty/null body before dropping title
        DB::table('forum_posts')
            ->where(function ($q) {
                $q->whereNull('body')->orWhere('body', '');
            })
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->update(['body' => DB::raw('title')]);

        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropIndex(['is_answered']);
            $table->dropColumn(['is_answered', 'title']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->string('title')->nullable()->after('curriculum');
            $table->boolean('is_answered')->default(false)->index()->after('image_path');
        });
    }
};
