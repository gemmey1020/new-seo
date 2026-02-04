<?php

namespace App\Models;

/**
 * Alias for backward compatibility.
 * The canonical User model is App\Models\Auth\User.
 * 
 * @deprecated Use App\Models\Auth\User directly
 */
class User extends \App\Models\Auth\User
{
    // This class exists only for backward compatibility.
    // All functionality is inherited from App\Models\Auth\User.
}

