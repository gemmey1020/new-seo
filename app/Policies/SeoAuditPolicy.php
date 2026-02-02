<?php

namespace App\Policies;

use App\Models\Auth\User;
use App\Models\Audit\SeoAudit;
use App\Models\Auth\Role;

class SeoAuditPolicy
{
    public function view(User $user, SeoAudit $audit): bool
    {
        return $this->isMember($user, $audit->site_id);
    }

    public function update(User $user, SeoAudit $audit): bool
    {
        // Fix issues
        return $this->hasRole($user, $audit->site_id, ['admin', 'seo_editor']);
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
