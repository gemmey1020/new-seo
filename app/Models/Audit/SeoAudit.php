<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Site\Site;
use App\Models\Seo\Page;
use App\Models\Auth\User;
use App\Models\Workflow\SeoTask;

/**
 * Class SeoAudit
 * 
 * @property int $id
 * @property int $site_id
 * @property int|null $page_id
 * @property int $rule_id
 * @property string $severity
 * @property string $status
 * @property string|null $description
 * @property array|null $evidence_json
 * @property \Illuminate\Support\Carbon $detected_at
 * @property \Illuminate\Support\Carbon|null $fixed_at
 * @property int|null $fixed_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SeoAudit extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seo_audits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'site_id',
        'page_id',
        'rule_id',
        'severity',
        'status',
        'description',
        'evidence_json',
        'detected_at',
        'fixed_at',
        'fixed_by_user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'evidence_json' => 'array',
        'detected_at' => 'datetime',
        'fixed_at' => 'datetime',
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
     * Get the page.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    /**
     * Get the rule.
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AuditRule::class, 'rule_id');
    }

    /**
     * Get the user who fixed the audit.
     */
    public function fixedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fixed_by_user_id');
    }

    /**
     * Get the tasks related to this audit.
     */
    public function auditTasks(): HasMany
    {
        return $this->hasMany(SeoTask::class, 'audit_id');
    }
}
