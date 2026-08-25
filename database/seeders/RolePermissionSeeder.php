<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed application roles and permissions.
     */
    public function run(): void
    {
        /*
         * Roles define who a user is.
         * Permissions define what a user can do.
         */

        $admin = Role::findOrCreate('admin');
        $editor = Role::findOrCreate('editor');
        $manager = Role::findOrCreate('manager');

        /*
         * Grants access to the admin panel.
         * Individual pages and actions require additional permissions.
         */
        Permission::findOrCreate('view admin');

        /*
         * Subject management
         */
        Permission::findOrCreate('create subjects');
        Permission::findOrCreate('edit subjects');
        Permission::findOrCreate('delete subjects');

        /*
         * Node (folder) management
         */
        Permission::findOrCreate('create nodes');
        Permission::findOrCreate('edit nodes');
        Permission::findOrCreate('delete nodes');

        /*
         * Resource management
         */
        Permission::findOrCreate('create resources');
        Permission::findOrCreate('edit resources');
        Permission::findOrCreate('delete resources');

        /*
         * Blog management
         */
        Permission::findOrCreate('create blogs');
        Permission::findOrCreate('edit blogs');
        Permission::findOrCreate('delete blogs');

        /*
         * Notice management
         */
        Permission::findOrCreate('edit notice');

        /*
         * User management.
         * Includes creating, editing, deleting users,
         * assigning roles, and assigning permissions.
         */
        Permission::findOrCreate('manage users');

        /*
         * Email management
         */
        Permission::findOrCreate('send email');

        /*
         * Maintenance
         */
        Permission::findOrCreate('clear cache');


        $admin->syncPermissions(Permission::all());
        // Administrators have unrestricted access to all features.

        $editor->syncPermissions([
            'view admin',
        ]);
        // Editors can access the admin panel by default.
        // Content-specific permissions are assigned individually to each editor.

        $manager->syncPermissions([
            'view admin',
        ]);
        // Managers can access the admin panel.
        // Additional manager permissions can be added here in the future if needed.

    }
}
