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
        // 1. Detach all children from parent folders
        DB::table('nodes')
            ->whereNotNull('parent_id')
            ->update(['parent_id' => null]);

        // 2. Delete legacy dummy shell parent folders ('পদ্য', 'গদ্য', 'সহপাঠ', etc.)
        $shellSlugs = ['poddo', 'goddo', 'sohopath'];
        $shellNames = ['পদ্য', 'গদ্য', 'সহপাঠ'];

        DB::table('nodes')
            ->whereIn('id', [219, 220, 221, 1735, 1736, 1737])
            ->orWhere(function ($query) use ($shellSlugs, $shellNames) {
                $query->whereIn('slug', $shellSlugs)
                    ->orWhereIn('name', $shellNames);
            })
            ->delete();

        // 3. Drop parent_id foreign key, drop composite unique index, and drop column
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropUnique(['parent_id', 'slug']);
            $table->dropColumn('parent_id');

            // Enforce subject_id + slug uniqueness for flat chapters
            $table->unique(['subject_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropUnique(['subject_id', 'slug']);
            $table->foreignId('parent_id')->nullable()->after('subject_id')->constrained('nodes')->cascadeOnDelete();
            $table->unique(['parent_id', 'slug']);
        });
    }
};
