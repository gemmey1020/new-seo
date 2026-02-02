<?php

namespace App\Policies;

use App\Models\Auth\User;
use App\Models\Crawl\CrawlRun;
use App\Models\Auth\Role;

class CrawlRunPolicy
{
    public function view(User $user, CrawlRun $run): bool
    {
        return $this->isMember($user, $run->site_id);
    }

    public function create(User $user): bool
    {
        return true; // Context site checked in controller usually
    }

    protected function isMember(User $user, int $siteId): bool
    {
        return $user->siteUsers()->where('site_id', $siteId)->exists();
    }
}
