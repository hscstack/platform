<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->json('images')->nullable()->after('image_path');
        });

        DB::table('forum_posts')
            ->whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->orderBy('id')
            ->each(function ($post) {
                DB::table('forum_posts')
                    ->where('id', $post->id)
                    ->update([
                        'images' => json_encode([$post->image_path]),
                    ]);
            });

        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('body');
        });

        DB::table('forum_posts')
            ->whereNotNull('images')
            ->orderBy('id')
            ->each(function ($post) {
                $decoded = json_decode($post->images, true);
                $first = is_array($decoded) && count($decoded) > 0 ? $decoded[0] : null;
                if ($first) {
                    DB::table('forum_posts')
                        ->where('id', $post->id)
                        ->update(['image_path' => $first]);
                }
            });

        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }
};
