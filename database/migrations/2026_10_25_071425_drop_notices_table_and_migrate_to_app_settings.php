<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate legacy singleton notice row into app_settings key-value store ('site_notice')
        if (Schema::hasTable('notices')) {
            $first = DB::table('notices')->first();
            if ($first) {
                DB::table('app_settings')->updateOrInsert(
                    ['key' => 'site_notice'],
                    [
                        'value' => json_encode([
                            'title' => $first->title,
                            'message' => $first->message,
                            'image' => $first->image,
                            'show_button' => (bool) $first->show_button,
                            'button_title' => $first->button_title,
                            'button_link' => $first->button_link,
                            'is_active' => (bool) $first->is_active,
                        ]),
                        'updated_at' => now(),
                    ]
                );
            }

            // Drop legacy notices table
            Schema::dropIfExists('notices');
        }
    }

    public function down(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->string('image')->nullable();
            $table->boolean('show_button')->default(false);
            $table->string('button_title')->nullable();
            $table->string('button_link')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }
};
