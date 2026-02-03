<?php

namespace App\Enums;

enum ActionClass: string
{
    case CLASS_A = 'A'; // Safe / Autonomous Candidates
    case CLASS_B = 'B'; // Gated / Human Approval Required
    case CLASS_C = 'C'; // Forbidden / Never Automate
}
