<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop blog views counter column from blogs table
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn('views');
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->unsignedBigInteger('views')->default(0)->after('reading_time');
        });
    }
};
