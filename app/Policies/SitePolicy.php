<?php

namespace App\Policies;

use App\Models\Auth\User;
use App\Models\Site\Site;
use App\Models\Auth\Role;

class SitePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Site $site): bool
    {
        // Must be a member of the site
        return $user->siteUsers()->where('site_id', $site->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create a NEW site
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Site $site): bool
    {
        return $this->hasRole($user, $site, ['admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Site $site): bool
    {
        // Spec implies Admin only, but strictly "cannot delete sites" for seo_editor implies Admin can.
        return $this->hasRole($user, $site, ['admin']);
    }

    /**
     * Helper to check roles on a site.
     */
    protected function hasRole(User $user, Site $site, array $allowedRoles): bool
    {
        $membership = $user->siteUsers()->where('site_id', $site->id)->first();
        
        if (!$membership) {
            return false;
        }

        $role = Role::find($membership->role_id);
        
        return $role && in_array($role->name, $allowedRoles);
    }
}
