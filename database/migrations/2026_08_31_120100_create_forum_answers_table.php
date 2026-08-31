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
        Schema::create('forum_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_post_id')->constrained('forum_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('forum_answers')->cascadeOnDelete();
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->integer('vote_score')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_answers');
    }
};
