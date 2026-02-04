<?php

namespace App\Models\Redirect;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Site\Site;
use App\Models\Auth\User;

class Redirect extends Model
{
    protected $table = 'redirects';

    protected $fillable = [
        'site_id',
        'from_url',
        'to_url',
        'type', // 301, 302, 410
        'status', // active, disabled
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
