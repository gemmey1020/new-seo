<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Site\Site;
use App\Models\Audit\SeoAudit;
use App\Models\Crawl\InternalLink;
use App\Models\Crawl\CrawlLog;
use App\Models\Workflow\SeoTask;

/**
 * Class Page
 * 
 * @property int $id
 * @property int $site_id
 * @property string $url
 * @property string $path
 * @property string|null $canonical_url
 * @property string|null $page_type
 * @property string $index_status
 * @property int|null $http_status_last
 * @property int $depth_level
 * @property \Illuminate\Support\Carbon|null $first_seen_at
 * @property \Illuminate\Support\Carbon|null $last_crawled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Page extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'site_id',
        'url',
        'path',
        'canonical_url',
        'page_type',
        'index_status',
        'http_status_last',
        'depth_level',
        'first_seen_at',
        'last_crawled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_crawled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the site that owns the page.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    /**
     * Get the SEO meta for the page.
     */
    public function meta(): HasOne
    {
        return $this->hasOne(SeoMeta::class, 'page_id');
    }

    /**
     * Get the schemas for the page.
     */
    public function schemas(): HasMany
    {
        return $this->hasMany(Schema::class, 'page_id');
    }

    /**
     * Get the audit findings for the page.
     */
    public function audits(): HasMany
    {
        return $this->hasMany(SeoAudit::class, 'page_id');
    }

    /**
     * Get the outbound internal links from this page.
     */
    public function outboundLinks(): HasMany
    {
        return $this->hasMany(InternalLink::class, 'from_page_id');
    }

    /**
     * Get the inbound internal links to this page.
     */
    public function inboundLinks(): HasMany
    {
        return $this->hasMany(InternalLink::class, 'to_page_id');
    }

    /**
     * Get the crawl logs for this page.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(CrawlLog::class, 'page_id');
    }

    /**
     * Get the tasks related to this page.
     */
    public function pageTasks(): HasMany
    {
        return $this->hasMany(SeoTask::class, 'page_id');
    }
}
