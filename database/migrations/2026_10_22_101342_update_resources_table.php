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
        Schema::table('resources', function (Blueprint $table) {
            $table->string('title', 255)->change();
            $table->renameColumn('content', 'description');
            $table->dropColumn(['file_path', 'external_url']);
        });

        DB::statement("ALTER TABLE resources MODIFY COLUMN resource_type VARCHAR(32) NOT NULL DEFAULT 'note'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE resources MODIFY COLUMN resource_type ENUM('note', 'question', 'pdf', 'image', 'video') NOT NULL");

        Schema::table('resources', function (Blueprint $table) {
            $table->renameColumn('description', 'content');
            $table->string('file_path', 255)->nullable();
            $table->text('external_url')->nullable();
            $table->string('title', 100)->change();
        });
    }
};
