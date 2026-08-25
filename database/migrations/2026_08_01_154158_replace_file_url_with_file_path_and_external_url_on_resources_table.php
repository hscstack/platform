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
            $table->string('file_path')->nullable()->after('content');
            $table->text('external_url')->nullable()->after('file_path');
        });

        DB::table('resources')
            ->whereNotNull('file_url')
            ->orderBy('id')
            ->each(function ($resource) {
                if (filter_var($resource->file_url, FILTER_VALIDATE_URL)) {
                    DB::table('resources')
                        ->where('id', $resource->id)
                        ->update([
                            'external_url' => $resource->file_url,
                        ]);
                } else {
                    DB::table('resources')
                        ->where('id', $resource->id)
                        ->update([
                            'file_path' => $resource->file_url,
                        ]);
                }
            });

        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn('file_url');
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->string('file_url', 500)->nullable()->after('content');
        });

        DB::table('resources')
            ->whereNotNull('external_url')
            ->update([
                'file_url' => DB::raw('external_url'),
            ]);

        DB::table('resources')
            ->whereNotNull('file_path')
            ->update([
                'file_url' => DB::raw('file_path'),
            ]);

        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn([
                'file_path',
                'external_url',
            ]);
        });
    }
};
