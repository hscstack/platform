<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->renameColumn('featured_image', 'featured_image_path');
        });

        DB::table('blogs')
            ->whereNotNull('featured_image_path')
            ->orderBy('id')
            ->each(function ($blog) {
                $path = str_replace('/storage/', '', $blog->featured_image_path);

                DB::table('blogs')
                    ->where('id', $blog->id)
                    ->update([
                        'featured_image_path' => $path,
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('blogs')
            ->whereNotNull('featured_image_path')
            ->orderBy('id')
            ->each(function ($blog) {
                DB::table('blogs')
                    ->where('id', $blog->id)
                    ->update([
                        'featured_image_path' => '/storage/'.ltrim($blog->featured_image_path, '/'),
                    ]);
            });

        Schema::table('blogs', function (Blueprint $table) {
            $table->renameColumn('featured_image_path', 'featured_image');
        });
    }
};
