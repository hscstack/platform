<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->unsignedInteger('upvotes_count')->default(0)->after('is_answered');
            $table->unsignedInteger('downvotes_count')->default(0)->after('upvotes_count');
        });

        Schema::table('forum_answers', function (Blueprint $table) {
            $table->unsignedInteger('upvotes_count')->default(0)->after('image_path');
            $table->unsignedInteger('downvotes_count')->default(0)->after('upvotes_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropColumn(['upvotes_count', 'downvotes_count']);
        });

        Schema::table('forum_answers', function (Blueprint $table) {
            $table->dropColumn(['upvotes_count', 'downvotes_count']);
        });
    }
};
