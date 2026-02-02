<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Site\SiteUser;
use App\Models\Site\Site;
use App\Models\Seo\SeoMetaVersion;
use App\Models\Seo\SchemaVersion;
use App\Models\Audit\SeoAudit;
use App\Models\Workflow\SeoTask;
use App\Models\Workflow\SeoTaskComment;

use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 * ...
 */
class User extends Authenticatable
{
    use HasApiTokens;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the site memberships for the user.
     */
    public function siteUsers(): HasMany
    {
        return $this->hasMany(SiteUser::class, 'user_id');
    }

    /**
     * Get the sites the user is a member of.
     */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'site_users', 'user_id', 'site_id')
                    ->withPivot(['role_id', 'status', 'joined_at']);
    }

    /**
     * Get the seo meta versions edited by this user.
     */
    public function seoMetaVersions(): HasMany
    {
        return $this->hasMany(SeoMetaVersion::class, 'user_id');
    }

    /**
     * Get the schema versions edited by this user.
     */
    public function schemaVersions(): HasMany
    {
        return $this->hasMany(SchemaVersion::class, 'user_id');
    }

    /**
     * Get the audits fixed by this user.
     */
    public function fixedAudits(): HasMany
    {
        return $this->hasMany(SeoAudit::class, 'fixed_by_user_id');
    }

    /**
     * Get the tasks created by this user.
     */
    public function createdTasks(): HasMany
    {
        return $this->hasMany(SeoTask::class, 'created_by_user_id');
    }

    /**
     * Get the tasks assigned to this user.
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(SeoTask::class, 'assigned_to_user_id');
    }

    /**
     * Get the comments made by this user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(SeoTaskComment::class, 'user_id');
    }
}
