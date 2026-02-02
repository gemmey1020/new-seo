<?php

namespace App\Models\Crawl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Site\Site;

/**
 * Class CrawlRun
 * 
 * @property int $id
 * @property int $site_id
 * @property string $mode
 * @property string $user_agent
 * @property string $status
 * @property int $pages_discovered
 * @property int $pages_crawled
 * @property int $errors_count
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CrawlRun extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'crawl_runs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'site_id',
        'mode',
        'user_agent',
        'status',
        'pages_discovered',
        'pages_crawled',
        'errors_count',
        'started_at',
        'finished_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the site.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    /**
     * Get the logs for this crawl run.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(CrawlLog::class, 'crawl_run_id');
    }
}
