<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Role::where('name', 'manager')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::findOrCreate('manager', 'web');
    }
};
