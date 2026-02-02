<?php

namespace App\Models\Crawl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Site\Site;
use App\Models\Seo\Page;

/**
 * Class InternalLink
 * 
 * @property int $id
 * @property int $site_id
 * @property int $from_page_id
 * @property int $to_page_id
 * @property string|null $anchor_text
 * @property bool $is_nofollow
 * @property bool $is_image_link
 * @property string|null $rel_attr
 * @property \Illuminate\Support\Carbon $first_seen_at
 * @property \Illuminate\Support\Carbon $last_seen_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class InternalLink extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'internal_links';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'site_id',
        'from_page_id',
        'to_page_id',
        'anchor_text',
        'is_nofollow',
        'is_image_link',
        'rel_attr',
        'first_seen_at',
        'last_seen_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_nofollow' => 'boolean',
        'is_image_link' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
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
     * Get the page where the link originates.
     */
    public function fromPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'from_page_id');
    }

    /**
     * Get the page where the link points to.
     */
    public function toPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'to_page_id');
    }
}
