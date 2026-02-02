<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Auth\User;

/**
 * Class SeoMetaVersion
 * 
 * @property int $id
 * @property int $seo_meta_id
 * @property int|null $user_id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $robots
 * @property string|null $og_title
 * @property string|null $og_description
 * @property string|null $og_image_url
 * @property string|null $twitter_card
 * @property string|null $twitter_title
 * @property string|null $twitter_description
 * @property string|null $twitter_image_url
 * @property string|null $change_note
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class SeoMetaVersion extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seo_meta_versions';

    /**
     * The timestamps are disabled because we only have created_at.
     *
     * @var bool
     */
    public $timestamps = false; // Manually handling created_at in migration useCurrent()

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'seo_meta_id',
        'user_id',
        'title',
        'description',
        'robots',
        'og_title',
        'og_description',
        'og_image_url',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image_url',
        'change_note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the seo meta parent.
     */
    public function meta(): BelongsTo
    {
        return $this->belongsTo(SeoMeta::class, 'seo_meta_id');
    }

    /**
     * Get the user who made the change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
