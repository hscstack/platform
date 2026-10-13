<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Permission::where('name', 'create resources')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::findOrCreate('create resources', 'web');
    }
};
