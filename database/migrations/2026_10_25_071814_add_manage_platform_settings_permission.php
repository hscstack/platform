<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $perm = Permission::findOrCreate('manage platform settings', 'web');
        $admin = Role::where('name', 'admin')->first();
        if ($admin && ! $admin->hasPermissionTo('manage platform settings')) {
            $admin->givePermissionTo($perm);
        }
    }

    public function down(): void
    {
        Permission::where('name', 'manage platform settings')->where('guard_name', 'web')->delete();
    }
};
