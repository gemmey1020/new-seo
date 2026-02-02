<?php

namespace App\Policies;

use App\Models\Auth\User;
use App\Models\Workflow\SeoTask;
use App\Models\Auth\Role;

class SeoTaskPolicy
{
    public function view(User $user, SeoTask $task): bool
    {
        return $this->isMember($user, $task->site_id);
    }

    public function create(User $user): bool
    {
        return true; 
    }

    public function update(User $user, SeoTask $task): bool
    {
        return $this->hasRole($user, $task->site_id, ['admin', 'seo_editor']);
    }

    public function delete(User $user, SeoTask $task): bool
    {
        // Only admin can delete tasks? Spec doesn't strictly say, assuming Admin.
        return $this->hasRole($user, $task->site_id, ['admin']);
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
