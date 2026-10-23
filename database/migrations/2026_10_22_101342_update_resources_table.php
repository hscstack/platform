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

            $table->index(['status', 'created_at']);
        });

        // Migrate existing single files/urls into the new items JSON array
        $existing = DB::table('resources')->get();
        foreach ($existing as $resource) {
            $format = match ($resource->resource_type) {
                'video' => 'video',
                'pdf' => 'pdf',
                default => 'image',
            };

            $source = $resource->external_url ?: $resource->file_path;
            $items = [];

            if ($source) {
                $items[] = [
                    'format' => $format,
                    'title' => null,
                    'source' => $source,
                ];
            }

            $thumbnail = null;
            if ($format === 'image' && $source) {
                $thumbnail = $source;
            } elseif ($format === 'video' && $source) {
                if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/', $source, $matches)) {
                    $thumbnail = "https://img.youtube.com/vi/{$matches[1]}/hqdefault.jpg";
                }
            }

            DB::table('resources')
                ->where('id', $resource->id)
                ->update([
                    'items' => ! empty($items) ? json_encode($items) : null,
                    'thumbnail' => $thumbnail,
                    'status' => 'approved', // Existing live resources remain approved
                ]);
        }

        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'external_url']);
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
            $table->string('file_path', 255)->nullable();
            $table->text('external_url')->nullable();
        });

        $resources = DB::table('resources')->whereNotNull('items')->get();
        foreach ($resources as $resource) {
            $items = json_decode($resource->items, true) ?: [];
            $first = $items[0] ?? null;
            if ($first && ! empty($first['source'])) {
                if (in_array($first['format'] ?? '', ['pdf', 'video']) || str_starts_with($first['source'], 'http')) {
                    DB::table('resources')->where('id', $resource->id)->update(['external_url' => $first['source']]);
                } else {
                    DB::table('resources')->where('id', $resource->id)->update(['file_path' => $first['source']]);
                }
            }
        }

        Schema::table('resources', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['items', 'thumbnail', 'status', 'admin_note', 'reviewed_by', 'reviewed_at']);
            $table->renameColumn('description', 'content');
            $table->string('title', 100)->change();
        });
    }
};
