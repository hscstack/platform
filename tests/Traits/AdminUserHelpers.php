<?php

namespace Tests\Traits;

use App\Models\User;
use Spatie\Permission\Models\Permission;

trait AdminUserHelpers
{
    protected function adminUserWithPermissions(array $permissions = []): User
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
