<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class RolePermission
 * 
 * @property int $role_id
 * @property int $permission_id
 */
class RolePermission extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'role_permission';
    
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'role_id',
        'permission_id',
    ];
}
