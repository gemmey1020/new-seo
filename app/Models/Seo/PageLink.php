<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageLink extends Model
{
    // No updated_at needed, we maintain last_seen_at manually or via boot
    public $timestamps = false; 

    protected $guarded = ['id'];

    protected $casts = [
        'is_internal' => 'boolean',
        'is_nofollow' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Site\Site::class);
    }

    public function fromPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'from_page_id');
    }

    public function toPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'to_page_id');
    }
}
