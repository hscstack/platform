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
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->enum('curriculum', ['hsc', 'ssc'])->index();
            $table->string('topic', 150)->nullable();
            $table->string('title', 255);
            $table->string('slug')->unique();
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->boolean('is_answered')->default(false)->index();
            $table->integer('vote_score')->default(0)->index();
            $table->unsignedInteger('answers_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
    }
};
