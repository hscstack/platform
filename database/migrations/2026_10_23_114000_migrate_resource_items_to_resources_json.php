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
        if (! Schema::hasColumn('resources', 'items')) {
            Schema::table('resources', function (Blueprint $table) {
                $table->json('items')->nullable()->after('description');
            });
        }

        if (Schema::hasTable('resource_items')) {
            $items = DB::table('resource_items')
                ->orderBy('resource_id')
                ->orderBy('id')
                ->get()
                ->groupBy('resource_id');

            foreach ($items as $resourceId => $group) {
                $payload = $group->map(function ($item) {
                    return [
                        'format' => $item->format,
                        'title' => $item->title,
                        'source' => $item->source,
                    ];
                })->values()->toArray();

                DB::table('resources')
                    ->where('id', $resourceId)
                    ->update(['items' => json_encode($payload)]);
            }

            Schema::dropIfExists('resource_items');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('resource_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->string('format', 32)->default('image');
            $table->string('title', 255)->nullable();
            $table->string('source', 2048);
            $table->timestamps();

            $table->index('resource_id');
        });

        if (Schema::hasColumn('resources', 'items')) {
            $resources = DB::table('resources')->whereNotNull('items')->get();
            foreach ($resources as $resource) {
                $items = json_decode($resource->items, true) ?: [];
                foreach ($items as $item) {
                    DB::table('resource_items')->insert([
                        'resource_id' => $resource->id,
                        'format' => $item['format'] ?? 'image',
                        'title' => $item['title'] ?? null,
                        'source' => $item['source'] ?? '',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::table('resources', function (Blueprint $table) {
                $table->dropColumn('items');
            });
        }
    }
};
