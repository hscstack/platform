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
            $table->string('thumbnail', 2048)->nullable()->after('content');
            $table->json('items')->nullable()->after('thumbnail');
            $table->string('status', 20)->default('pending')->after('items');
            $table->text('admin_note')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('admin_note')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->dropColumn(['file_path', 'external_url']);

            $table->index(['status', 'created_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE resources MODIFY COLUMN resource_type VARCHAR(32) NOT NULL DEFAULT 'note'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE resources MODIFY COLUMN resource_type ENUM('note', 'question', 'pdf', 'image', 'video') NOT NULL");
        }

        Schema::table('resources', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['items', 'thumbnail', 'status', 'admin_note', 'reviewed_by', 'reviewed_at']);
            $table->string('file_path', 255)->nullable();
            $table->text('external_url')->nullable();
            $table->renameColumn('description', 'content');
            $table->string('title', 100)->change();
        });
    }
};
