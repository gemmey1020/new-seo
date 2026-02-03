<?php

namespace App\Enums;

enum ActionStatus: string
{
    case ALLOWED = 'ALLOWED';
    case DENIED = 'DENIED';
    case ERROR = 'ERROR';
    case PENDING_APPROVAL = 'PENDING_APPROVAL';
}
