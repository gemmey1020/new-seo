<?php

namespace App\Models\Crawl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Site\Site;

/**
 * Class CrawlRun
 * 
 * ENGINE CORE: State Machine for Crawl Execution
 * 
 * @property int $id
 * @property int $site_id
 * @property string $mode
 * @property string $user_agent
 * @property string $status
 * @property int $pages_discovered
 * @property int $pages_crawled
 * @property int $errors_count
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CrawlRun extends Model
{
    // State Machine Constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

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
        'error_message',
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
     * Valid state transitions.
     */
    private const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_RUNNING, self::STATUS_FAILED],
        self::STATUS_RUNNING => [self::STATUS_COMPLETED, self::STATUS_FAILED],
        // No transitions out of completed/failed
    ];

    /**
     * Transition to a new state with validation.
     * 
     * @param string $status Target status
     * @throws \DomainException If transition is invalid
     */
    public function transitionTo(string $status): void
    {
        $allowed = self::TRANSITIONS[$this->status] ?? [];

        if (!in_array($status, $allowed)) {
            throw new \DomainException(
                "Invalid state transition: {$this->status} → {$status}"
            );
        }

        $this->status = $status;
        
        if ($status === self::STATUS_RUNNING) {
            $this->started_at = now();
        }
        
        if (in_array($status, [self::STATUS_COMPLETED, self::STATUS_FAILED])) {
            $this->finished_at = now();
        }
        
        $this->save();
    }

    /**
     * Mark as failed with error message.
     */
    public function fail(string $message): void
    {
        $this->error_message = $message;
        $this->transitionTo(self::STATUS_FAILED);
    }

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

