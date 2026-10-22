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
        Schema::table('resources', function (Blueprint $table) {
            $table->integer('vote_score')->default(0)->index()->after('description');
            $table->unsignedInteger('upvotes_count')->default(0)->after('vote_score');
            $table->unsignedInteger('downvotes_count')->default(0)->after('upvotes_count');
        });

        Schema::create('resource_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('value'); // 1 = upvote, -1 = downvote
            $table->timestamps();

            $table->unique(['resource_id', 'user_id']);
            $table->index(['resource_id', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_votes');

        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn(['vote_score', 'upvotes_count', 'downvotes_count']);
        });
    }
};
