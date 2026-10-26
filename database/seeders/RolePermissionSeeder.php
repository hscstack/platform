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

        /*
         * Grants access to the admin panel.
         * Individual pages and actions require additional permissions.
         */
        Permission::findOrCreate('view admin');

        /*
         * Curriculum management (Subjects & Flat Chapters)
         */
        Permission::findOrCreate('manage curriculum');

        /*
         * Resource management
         */
        Permission::findOrCreate('moderate resources');
        Permission::findOrCreate('manage resources');

        /*
         * Blog management
         */
        Permission::findOrCreate('create blogs');
        Permission::findOrCreate('manage blogs');

        /*
         * Platform Settings & Maintenance
         */
        Permission::findOrCreate('manage settings');

        /*
         * User management
         */
        Permission::findOrCreate('view users');
        Permission::findOrCreate('manage users');
        Permission::findOrCreate('impersonate users');

        /*
         * Email management
         */
        Permission::findOrCreate('send email');

        /*
         * Global Chat management
         */
        Permission::findOrCreate('manage chat');

        /*
         * Support Ticket management
         */
        Permission::findOrCreate('manage tickets');

        /*
         * Forum management
         */
        Permission::findOrCreate('manage forums');

        /*
         * Product management
         */
        Permission::findOrCreate('manage products');

        $admin->syncPermissions(Permission::all());
        // Administrators have unrestricted access to all features.

        $editor->syncPermissions([
            'view admin',
        ]);
        // Editors can access the admin panel by default.
        // Content-specific permissions are assigned individually to each editor.
    }
}
