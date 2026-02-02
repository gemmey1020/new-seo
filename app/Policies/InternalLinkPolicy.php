<?php

namespace App\Policies;

use App\Models\Auth\User;
use App\Models\Crawl\InternalLink;
use App\Models\Auth\Role;

class InternalLinkPolicy
{
    public function view(User $user, InternalLink $link): bool
    {
        return $this->isMember($user, $link->site_id);
    }

    protected function isMember(User $user, int $siteId): bool
    {
        return $user->siteUsers()->where('site_id', $siteId)->exists();
    }
}
