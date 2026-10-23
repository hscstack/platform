<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $formatSlugs = [
            'class', 'classes', 'klas', 'klas-sors-2',
            'handnote', 'handnotes', 'hzandnot',
            'dagano-boi', 'dagano-bi', 'marked-book',
            'practical-01', 'practical-02', 'practical-03', 'practical-04',
            'practical-05', 'practical-06', 'practical-07', 'practical-08',
        ];

        $formatNames = [
            'ক্লাস', 'হ্যান্ডনোট', 'handnote', 'class', 'classes', 'handnotes',
            'ক্লাস সোর্স - ১', 'ক্লাস সোর্স - ২', 'ক্লাস সোর্স ১', 'ক্লাস সোর্স ২',
            'দাগানো বই', 'Marked Book',
        ];

        DB::table('nodes')
            ->whereNotNull('parent_id')
            ->where(function ($query) use ($formatSlugs, $formatNames) {
                $query->whereIn('slug', $formatSlugs)
                    ->orWhereIn('name', $formatNames);
            })
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way cleanup migration: old dummy format subfolders are deprecated
    }
};
