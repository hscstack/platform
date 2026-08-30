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
        Schema::dropIfExists('traffic');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('traffic', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->unique();
            $table->string('user_agent');
            $table->string('source');
            $table->unsignedBigInteger('visits')->default(0);
            $table->timestamps();
        });
    }
};
