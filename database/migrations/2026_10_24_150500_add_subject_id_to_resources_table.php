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
            $table->foreignId('subject_id')->nullable()->after('user_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedBigInteger('node_id')->nullable()->change();
        });

        // Backfill subject_id from nodes
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('
                UPDATE resources
                SET subject_id = (SELECT subject_id FROM nodes WHERE nodes.id = resources.node_id)
                WHERE subject_id IS NULL AND node_id IS NOT NULL
            ');
        } else {
            DB::statement('
                UPDATE resources r
                JOIN nodes n ON r.node_id = n.id
                SET r.subject_id = n.subject_id
                WHERE r.subject_id IS NULL AND r.node_id IS NOT NULL
            ');
        }
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropColumn('subject_id');
            $table->unsignedBigInteger('node_id')->nullable(false)->change();
        });
    }
};
