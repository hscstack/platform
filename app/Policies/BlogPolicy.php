<?php

namespace App\Policies;

use App\Models\Blog;
use App\Models\User;

class BlogPolicy
{
    /**
     * Determine whether the user can update the blog.
     */
    public function update(User $user, Blog $blog): bool
    {
        return $user->id === $blog->user_id || $user->can('edit blogs');
    }
}
