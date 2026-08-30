<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('resources')
            ->whereNotNull('file_path')
            ->orderBy('id')
            ->chunkById(100, function ($resources) {
                foreach ($resources as $resource) {
                    DB::table('resources')
                        ->where('id', $resource->id)
                        ->update([
                            'file_path' => ltrim(
                                str_replace('/storage/', '', $resource->file_path),
                                '/'
                            ),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('resources')
            ->whereNotNull('file_path')
            ->orderBy('id')
            ->chunkById(100, function ($resources) {
                foreach ($resources as $resource) {
                    DB::table('resources')
                        ->where('id', $resource->id)
                        ->update([
                            'file_path' => 'storage/'.ltrim($resource->file_path, '/'),
                        ]);
                }
            });
    }
};
