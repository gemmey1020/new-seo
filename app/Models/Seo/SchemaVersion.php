<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Auth\User;

/**
 * Class SchemaVersion
 * 
 * @property int $id
 * @property int $schema_id
 * @property int|null $user_id
 * @property string $json_ld
 * @property string|null $change_note
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class SchemaVersion extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'schema_versions';

    /**
     * The timestamps are disabled because we only have created_at.
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
        'schema_id',
        'user_id',
        'json_ld',
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
     * Get the schema parent.
     */
    public function schema(): BelongsTo
    {
        return $this->belongsTo(Schema::class, 'schema_id');
    }

    /**
     * Get the user who made the change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
