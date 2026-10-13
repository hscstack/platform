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
        Schema::table('subjects', function (Blueprint $table) {
            $table->boolean('is_optional')->default(false)->after('is_trackable');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('optional_subject_ids')->nullable()->after('group');
        });

        // Seed initial known optional subjects by unique slug:
        $optionalSlugs = [
            // HSC Science
            'hsc-hmath-1st',
            'hsc-hmath-2nd',
            'hsc-biology-1st',
            'hsc-biology-2nd',
            // SSC Science
            'ssc-biology',
            'ssc-higher-math',
            // HSC Commerce
            'hsc-finance-1st',
            'hsc-finance-2nd',
            'hsc-marketing-1st',
            'hsc-marketing-2nd',
            // HSC Humanities
            'hsc-economics-1st',
            'hsc-economics-2nd',
            'hsc-logic-1st',
            'hsc-logic-2nd',
            'hsc-islamic-history-1st',
            'hsc-islamic-history-2nd',
        ];

        DB::table('subjects')->whereIn('slug', $optionalSlugs)->update(['is_optional' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('optional_subject_ids');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('is_optional');
        });
    }
};
