<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Schema
 * 
 * @property int $id
 * @property int $page_id
 * @property string $schema_type
 * @property string $json_ld
 * @property bool $is_active
 * @property bool $is_validated
 * @property string|null $validation_provider
 * @property string|null $validation_errors
 * @property \Illuminate\Support\Carbon|null $last_validated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Schema extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'schemas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'page_id',
        'schema_type',
        'json_ld',
        'is_active',
        'is_validated',
        'validation_provider',
        'validation_errors',
        'last_validated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_validated' => 'boolean',
        'last_validated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the page that owns the schema.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    /**
     * Get the history versions of the schema.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(SchemaVersion::class, 'schema_id');
    }
}
