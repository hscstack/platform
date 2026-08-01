<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('resources')
            ->whereNotNull('file_path')
            ->update([
                'file_path' => DB::raw("LTRIM(REPLACE(file_path, '/storage/', ''), '/')"),
            ]);
    }

    public function down(): void
    {
        DB::table('resources')
            ->whereNotNull('file_path')
            ->update([
                'file_path' => DB::raw("'storage/' || file_path"),
            ]);
    }
};
