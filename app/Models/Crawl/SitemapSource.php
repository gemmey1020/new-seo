<?php

namespace App\Models\Crawl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Site\Site;

/**
 * Class SitemapSource
 * 
 * @property int $id
 * @property int $site_id
 * @property string $sitemap_url
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_fetched_at
 * @property string|null $last_error
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SitemapSource extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sitemap_sources';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'site_id',
        'sitemap_url',
        'status',
        'last_fetched_at',
        'last_error',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_fetched_at' => 'datetime',
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
}
