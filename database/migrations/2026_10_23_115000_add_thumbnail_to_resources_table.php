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
        if (! Schema::hasColumn('resources', 'thumbnail')) {
            Schema::table('resources', function (Blueprint $table) {
                $table->string('thumbnail', 2048)->nullable()->after('description');
            });
        }

        $resources = DB::table('resources')->whereNotNull('items')->get();

        foreach ($resources as $resource) {
            $items = json_decode($resource->items, true) ?: [];
            $first = $items[0] ?? null;

            if (! $first) {
                continue;
            }

            $format = $first['format'] ?? null;
            $source = $first['source'] ?? null;
            $thumbnail = null;

            if ($format === 'image' && $source) {
                $thumbnail = $source;
            } elseif ($format === 'video' && $source) {
                if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/', $source, $matches)) {
                    $thumbnail = "https://img.youtube.com/vi/{$matches[1]}/hqdefault.jpg";
                }
            }

            if ($thumbnail) {
                DB::table('resources')
                    ->where('id', $resource->id)
                    ->update(['thumbnail' => $thumbnail]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('resources', 'thumbnail')) {
            Schema::table('resources', function (Blueprint $table) {
                $table->dropColumn('thumbnail');
            });
        }
    }
};
