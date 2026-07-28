<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

if (! function_exists('adminUserWithPermissions')) {
    function adminUserWithPermissions(array $permissions = []): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        if (! empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }
}
