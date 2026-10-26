<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
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

        // 1. Create the new unified permissions
        $manageCurriculum = Permission::findOrCreate('manage curriculum', 'web');
        $manageResources = Permission::findOrCreate('manage resources', 'web');
        $manageUsers = Permission::findOrCreate('manage users', 'web');
        $manageSettings = Permission::findOrCreate('manage settings', 'web');

        // 2. Grant new permissions to the admin role
        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo([
                $manageCurriculum,
                $manageResources,
                $manageUsers,
                $manageSettings,
            ]);
        }

        // 3. Migrate custom user permission grants
        $curriculumOld = ['create subjects', 'edit subjects', 'delete subjects', 'create nodes', 'edit nodes', 'delete nodes', 'freeze nodes'];
        try {
            $usersWithCurriculum = User::permission($curriculumOld)->get();
            foreach ($usersWithCurriculum as $user) {
                $user->givePermissionTo($manageCurriculum);
            }
        } catch (Throwable) {
        }

        $resourcesOld = ['edit resources', 'delete resources'];
        try {
            $usersWithResources = User::permission($resourcesOld)->get();
            foreach ($usersWithResources as $user) {
                $user->givePermissionTo($manageResources);
            }
        } catch (Throwable) {
        }

        $usersOld = ['create users', 'edit users', 'delete users'];
        try {
            $usersWithUsers = User::permission($usersOld)->get();
            foreach ($usersWithUsers as $user) {
                $user->givePermissionTo($manageUsers);
            }
        } catch (Throwable) {
        }

        $settingsOld = ['manage platform settings', 'edit notice', 'manage peers', 'clear cache'];
        try {
            $usersWithSettings = User::permission($settingsOld)->get();
            foreach ($usersWithSettings as $user) {
                $user->givePermissionTo($manageSettings);
            }
        } catch (Throwable) {
        }

        // 4. Delete obsolete permissions from database
        $allObsolete = array_merge($curriculumOld, $resourcesOld, $usersOld, $settingsOld);
        Permission::whereIn('name', $allObsolete)
            ->where('guard_name', 'web')
            ->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $curriculumOld = ['create subjects', 'edit subjects', 'delete subjects', 'create nodes', 'edit nodes', 'delete nodes', 'freeze nodes'];
        $resourcesOld = ['edit resources', 'delete resources'];
        $usersOld = ['create users', 'edit users', 'delete users'];
        $settingsOld = ['manage platform settings', 'edit notice', 'manage peers', 'clear cache'];

        $allToRestore = array_merge($curriculumOld, $resourcesOld, $usersOld, $settingsOld);
        foreach ($allToRestore as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo($allToRestore);
        }

        Permission::whereIn('name', [
            'manage curriculum',
            'manage resources',
            'manage users',
            'manage settings',
        ])->where('guard_name', 'web')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
