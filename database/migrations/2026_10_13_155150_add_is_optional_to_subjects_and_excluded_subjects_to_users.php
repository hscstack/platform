<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->json('excluded_subject_ids')->nullable()->after('group');
        });

        // Seed initial known optional subjects:
        // HSC Science: Higher Math 1st & 2nd (9, 10), Biology 1st & 2nd (11, 12)
        // SSC Science: Biology (24), Higher Math (25)
        // HSC Commerce: Production Management 1st & 2nd (48, 49), Finance 1st & 2nd (46, 47)
        // HSC Humanities: Islamic History 1st & 2nd (56, 57), Logic 1st & 2nd (54, 55), Economics 1st & 2nd (52, 53)
        $optionalIds = [
            // HSC Science
            9, 10, 11, 12,
            // SSC Science
            24, 25,
            // HSC Commerce
            46, 47, 48, 49,
            // HSC Humanities
            52, 53, 54, 55, 56, 57,
        ];

        DB::table('subjects')->whereIn('id', $optionalIds)->update(['is_optional' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('excluded_subject_ids');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('is_optional');
        });
    }
};
