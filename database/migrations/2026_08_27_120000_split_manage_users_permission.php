<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $newPermissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',
            'impersonate users',
        ];

        foreach ($newPermissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Grant new permissions to admin role
        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo($newPermissions);
        }

        // Transfer any roles or direct users who had legacy 'manage users' permission
        $legacyPermission = Permission::where('name', 'manage users')->where('guard_name', 'web')->first();
        if ($legacyPermission) {
            $rolesWithManageUsers = Role::permission('manage users')->get();
            foreach ($rolesWithManageUsers as $role) {
                $role->givePermissionTo($newPermissions);
            }

            $usersWithManageUsers = User::permission('manage users')->get();
            foreach ($usersWithManageUsers as $user) {
                $user->givePermissionTo($newPermissions);
            }

            $legacyPermission->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $legacyPermission = Permission::findOrCreate('manage users', 'web');

        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo($legacyPermission);
        }

        $newPermissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',
            'impersonate users',
        ];

        foreach ($newPermissions as $name) {
            $perm = Permission::where('name', $name)->where('guard_name', 'web')->first();
            $perm?->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
