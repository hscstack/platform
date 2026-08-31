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
            $table->boolean('is_locked')->default(false)->index()->after('is_answered');
            $table->boolean('is_published')->default(true)->index()->after('is_locked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropIndex(['is_locked']);
            $table->dropIndex(['is_published']);
            $table->dropColumn(['is_locked', 'is_published']);
        });
    }
};
