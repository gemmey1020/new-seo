<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Auth\User;
use App\Models\Seo\Page;
use App\Models\Audit\SeoAudit;
use App\Models\Crawl\InternalLink;
use App\Models\Crawl\CrawlRun;
use App\Models\Crawl\CrawlLog;
use App\Models\Crawl\SitemapSource;
use App\Models\Workflow\SeoTask;

/**
 * Class Site
 * 
 * @property int $id
 * @property string $name
 * @property string $domain
 * @property string $locale_default
 * @property string $timezone
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Site extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sites';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'domain',
        'locale_default',
        'timezone',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the memberships for the site.
     */
    public function siteUsers(): HasMany
    {
        return $this->hasMany(SiteUser::class, 'site_id');
    }

    /**
     * Get the users who are members of the site.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'site_users', 'site_id', 'user_id')
                    ->withPivot(['role_id', 'status', 'joined_at']);
    }

    /**
     * Get the pages for the site.
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'site_id');
    }

    /**
     * Get the audits for the site.
     */
    public function audits(): HasMany
    {
        return $this->hasMany(SeoAudit::class, 'site_id');
    }

    /**
     * Get the internal links for the site.
     */
    public function internalLinks(): HasMany
    {
        return $this->hasMany(InternalLink::class, 'site_id');
    }

    /**
     * Get the crawl runs for the site.
     */
    public function crawlRuns(): HasMany
    {
        return $this->hasMany(CrawlRun::class, 'site_id');
    }

    /**
     * Get the crawl logs for the site.
     */
    public function crawlLogs(): HasMany
    {
        return $this->hasMany(CrawlLog::class, 'site_id');
    }

    /**
     * Get the sitemap sources for the site.
     */
    public function sitemapSources(): HasMany
    {
        return $this->hasMany(SitemapSource::class, 'site_id');
    }

    /**
     * Get the tasks for the site.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(SeoTask::class, 'site_id');
    }
}
