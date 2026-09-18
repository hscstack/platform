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
            $table->boolean('is_trackable')->default(true)->after('sort_order');
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->boolean('is_trackable')->default(false)->after('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('is_trackable');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('is_trackable');
        });
    }
};
