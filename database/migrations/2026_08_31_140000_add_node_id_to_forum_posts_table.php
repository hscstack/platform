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
            $table->foreignId('node_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('nodes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->dropColumn('node_id');
        });
    }
};
