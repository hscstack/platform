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
        if ($resource->node?->isEffectivelyFrozen()) {
            return false;
        }

        return $user->id === $resource->user_id || $user->can('edit resources');
    }
}
