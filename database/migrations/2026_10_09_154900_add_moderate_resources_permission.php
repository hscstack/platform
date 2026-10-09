<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::flush();

        $permission = Permission::findOrCreate('moderate resources', 'web');

        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo($permission);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::flush();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::flush();

        $permission = Permission::where('name', 'moderate resources')->where('guard_name', 'web')->first();
        $permission?->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::flush();
    }
};
