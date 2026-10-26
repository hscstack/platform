<?php

namespace App\Policies;

use App\Models\Resource;
use App\Models\User;

class ResourcePolicy
{
    /**
     * Determine whether the user can update the resource.
     */
    public function update(User $user, Resource $resource): bool
    {
        if ($resource->node?->is_frozen) {
            return false;
        }

        if ($user->isBanned()) {
            return false;
        }

        return $user->can('edit resources') || $user->id === $resource->user_id;
    }

    /**
     * Determine whether the user can delete the resource.
     */
    public function delete(User $user, Resource $resource): bool
    {
        if ($resource->node?->is_frozen) {
            return false;
        }

        return $user->can('delete resources');
    }
}
