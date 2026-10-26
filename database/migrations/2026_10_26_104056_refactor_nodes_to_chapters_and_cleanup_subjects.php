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
        $driver = DB::getDriverName();

        // 1. Rename table 'nodes' to 'chapters' if not already renamed
        if (Schema::hasTable('nodes') && ! Schema::hasTable('chapters')) {
            Schema::rename('nodes', 'chapters');
        }

        // 2. Drop unique index and obsolete columns from 'chapters' (is_frozen, is_trackable, slug)
        if (Schema::hasTable('chapters')) {
            if ($driver === 'sqlite') {
                try {
                    DB::statement('DROP INDEX IF EXISTS nodes_subject_id_slug_unique');
                } catch (Throwable $e) {
                }
            } else {
                try {
                    Schema::table('chapters', function (Blueprint $table) {
                        $table->index('subject_id', 'chapters_subject_id_index');
                    });
                } catch (Throwable $e) {
                }

                try {
                    Schema::table('chapters', function (Blueprint $table) {
                        $table->dropUnique('nodes_subject_id_slug_unique');
                    });
                } catch (Throwable $e) {
                }
            }

            Schema::table('chapters', function (Blueprint $table) {
                if (Schema::hasColumn('chapters', 'is_frozen')) {
                    $table->dropColumn('is_frozen');
                }
                if (Schema::hasColumn('chapters', 'is_trackable')) {
                    $table->dropColumn('is_trackable');
                }
                if (Schema::hasColumn('chapters', 'slug')) {
                    $table->dropColumn('slug');
                }
            });
        }

        // 3. Rename table 'node_completions' to 'chapter_completions'
        if (Schema::hasTable('node_completions') && ! Schema::hasTable('chapter_completions')) {
            Schema::rename('node_completions', 'chapter_completions');
        }

        // 4. Update 'chapter_completions' foreign key column: node_id -> chapter_id
        if (Schema::hasTable('chapter_completions')) {
            if (Schema::hasColumn('chapter_completions', 'node_id')) {
                if ($driver !== 'sqlite') {
                    try {
                        Schema::table('chapter_completions', function (Blueprint $table) {
                            $table->dropForeign('node_completions_node_id_foreign');
                        });
                    } catch (Throwable $e) {
                    }
                }
                Schema::table('chapter_completions', function (Blueprint $table) {
                    $table->renameColumn('node_id', 'chapter_id');
                });
            }
        }

        // 5. Update 'resources' column: node_id -> chapter_id
        if (Schema::hasTable('resources')) {
            if (Schema::hasColumn('resources', 'node_id')) {
                if ($driver !== 'sqlite') {
                    try {
                        Schema::table('resources', function (Blueprint $table) {
                            $table->dropForeign('resources_node_id_foreign');
                        });
                    } catch (Throwable $e) {
                    }
                }
                Schema::table('resources', function (Blueprint $table) {
                    $table->renameColumn('node_id', 'chapter_id');
                });
            }
        }

        // 6. Update 'forum_posts' column: node_id -> chapter_id
        if (Schema::hasTable('forum_posts')) {
            if (Schema::hasColumn('forum_posts', 'node_id')) {
                if ($driver !== 'sqlite') {
                    try {
                        Schema::table('forum_posts', function (Blueprint $table) {
                            $table->dropForeign('forum_posts_node_id_foreign');
                        });
                    } catch (Throwable $e) {
                    }
                }
                Schema::table('forum_posts', function (Blueprint $table) {
                    $table->renameColumn('node_id', 'chapter_id');
                });
            }
        }

        // 7. Cleanup 'subjects' table: drop slug unique index, then slug, tailwind_format, icon, is_trackable
        if (Schema::hasTable('subjects')) {
            if ($driver === 'sqlite') {
                try {
                    DB::statement('DROP INDEX IF EXISTS subjects_slug_unique');
                } catch (Throwable $e) {
                }
            } else {
                try {
                    Schema::table('subjects', function (Blueprint $table) {
                        $table->dropUnique('subjects_slug_unique');
                    });
                } catch (Throwable $e) {
                }
            }

            Schema::table('subjects', function (Blueprint $table) {
                if (Schema::hasColumn('subjects', 'slug')) {
                    $table->dropColumn('slug');
                }
                if (Schema::hasColumn('subjects', 'tailwind_format')) {
                    $table->dropColumn('tailwind_format');
                }
                if (Schema::hasColumn('subjects', 'icon')) {
                    $table->dropColumn('icon');
                }
                if (Schema::hasColumn('subjects', 'is_trackable')) {
                    $table->dropColumn('is_trackable');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
