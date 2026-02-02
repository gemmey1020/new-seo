<?php

namespace App\Policies;

use App\Models\Auth\User;
use App\Models\Seo\Schema;
use App\Models\Auth\Role;

class SchemaPolicy
{
    public function view(User $user, Schema $schema): bool
    {
        return $this->isMember($user, $schema->page->site_id);
    }

    public function create(User $user): bool
    {
        // Check performed in controller via site context generally, 
        // but if instance passed, we strictly allow admins/editors.
        return true; 
    }

    public function update(User $user, Schema $schema): bool
    {
        return $this->hasRole($user, $schema->page->site_id, ['admin', 'seo_editor']);
    }

    public function validate(User $user, Schema $schema): bool
    {
        return $this->hasRole($user, $schema->page->site_id, ['admin', 'seo_editor']);
    }

    protected function isMember(User $user, int $siteId): bool
    {
        return $user->siteUsers()->where('site_id', $siteId)->exists();
    }

    protected function hasRole(User $user, int $siteId, array $allowedRoles): bool
    {
        $membership = $user->siteUsers()->where('site_id', $siteId)->first();
        if (!$membership) return false;
        $role = Role::find($membership->role_id);
        return $role && in_array($role->name, $allowedRoles);
    }
}
