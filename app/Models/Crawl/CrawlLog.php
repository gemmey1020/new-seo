<?php

namespace App\Models\Crawl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Site\Site;
use App\Models\Seo\Page;

/**
 * Class CrawlLog
 * 
 * @property int $id
 * @property int $site_id
 * @property int|null $page_id
 * @property int $crawl_run_id
 * @property string|null $user_agent
 * @property int|null $status_code
 * @property int|null $response_ms
 * @property int|null $bytes
 * @property string|null $content_type
 * @property string|null $final_url
 * @property bool $blocked_by_robots
 * @property bool $blocked_by_meta
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $crawled_at
 */
class CrawlLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'crawl_logs';

    /**
     * The timestamps are disabled (only crawled_at).
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'site_id',
        'page_id',
        'crawl_run_id',
        'user_agent',
        'status_code',
        'response_ms',
        'bytes',
        'content_type',
        'final_url',
        'blocked_by_robots',
        'blocked_by_meta',
        'notes',
        'crawled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'blocked_by_robots' => 'boolean',
        'blocked_by_meta' => 'boolean',
        'crawled_at' => 'datetime',
    ];

    /**
     * Get the site.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    /**
     * Get the page.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    /**
     * Get the crawl run.
     */
    public function crawlRun(): BelongsTo
    {
        return $this->belongsTo(CrawlRun::class, 'crawl_run_id');
    }
}
