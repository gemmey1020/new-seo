<?php

namespace App\Policies;

use App\Models\Auth\User;
use App\Models\Workflow\SeoTaskComment;
use App\Models\Auth\Role;

class SeoTaskCommentPolicy
{
    public function view(User $user, SeoTaskComment $comment): bool
    {
        return $this->isMember($user, $comment->task->site_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    protected function isMember(User $user, int $siteId): bool
    {
        return $user->siteUsers()->where('site_id', $siteId)->exists();
    }
}
