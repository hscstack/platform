<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->boolean('is_frozen')->default(false)->after('is_trackable');
        });

        if (class_exists(Permission::class)) {
            $permission = Permission::firstOrCreate([
                'name' => 'freeze nodes',
                'guard_name' => 'web',
            ]);

            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $adminRole->givePermissionTo($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('is_frozen');
        });

        if (class_exists(Permission::class)) {
            Permission::where('name', 'freeze nodes')->delete();
        }
    }
};
