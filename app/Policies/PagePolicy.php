<?php

namespace App\Policies;

use App\Models\Auth\User;
use App\Models\Seo\Page;
use App\Models\Auth\Role;

class PagePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Page $page): bool
    {
        return $this->isMember($user, $page->site_id);
    }

    /**
     * Determine whether the user can create models.
     * Note: Create usually passed as just class or check on Site.
     * Logic here assumes we verify site context in Controller before calling 'create' policy?
     * Or usually passed as $user->can('create', [Page::class, $site]).
     * For simplicity complying with resource controller conventions:
     */
    public function create(User $user, Page $page = null): bool
    {
        // If an instance is passed (e.g. check on site context), use it.
        // Otherwise, controller checks membership. 
        // We generally enforce this per-site.
        // If $page is null, we can't check site.
        // Assuming usage: $user->can('create', $site) ? No this is PagePolicy.
        // We will assume the controller authorizes explicitly or passes a dummy Page with site_id.
        // Let's implement logic if $page instance exists.
        
        if ($page) {
             return $this->hasRole($user, $page->site_id, ['admin', 'seo_editor']);
        }
        return true; 
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Page $page): bool
    {
        return $this->hasRole($user, $page->site_id, ['admin', 'seo_editor']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Page $page): bool
    {
        // Section 12 API Contract says DELETE is (admin) ONLY.
        return $this->hasRole($user, $page->site_id, ['admin']);
    }

    protected function isMember(User $user, int $siteId): bool
    {
        return $user->siteUsers()->where('site_id', $siteId)->exists();
    }

    protected function hasRole(User $user, int $siteId, array $allowedRoles): bool
    {
        $membership = $user->siteUsers()->where('site_id', $siteId)->first();
        
        if (!$membership) {
            return false;
        }

        $role = Role::find($membership->role_id);
        
        return $role && in_array($role->name, $allowedRoles);
    }
}
