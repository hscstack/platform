<?php

namespace App\Policies;

use App\Models\Blog;
use App\Models\User;

class BlogPolicy
{
    /**
     * Determine whether the user can create blogs.
     */
    public function create(User $user): bool
    {
        return $user->can('create blogs') || $user->can('manage blogs');
    }

    /**
     * Determine whether the user can update the blog.
     * Authors can edit their own blog, authority with 'manage blogs' can edit anyone's.
     */
    public function update(User $user, Blog $blog): bool
    {
        return $user->id === $blog->user_id || $user->can('manage blogs');
    }

    /**
     * Determine whether the user can delete the blog.
     * Authors can delete their own blog, authority with 'manage blogs' can delete anyone's.
     */
    public function delete(User $user, Blog $blog): bool
    {
        return $user->id === $blog->user_id || $user->can('manage blogs');
    }

    /**
     * Determine whether the user can view the blog.
     * Published blogs can be viewed by anyone.
     * Unpublished blogs can only be viewed by the author or authorities with 'manage blogs'.
     */
    public function view(?User $user, Blog $blog): bool
    {
        if ($blog->is_published) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->id === $blog->user_id || $user->can('manage blogs');
    }
}
