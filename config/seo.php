<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Engine Phase
    |--------------------------------------------------------------------------
    |
    | Defines the current operational phase of the SEO Engine.
    | Used by the Presentation Layer to determine how to display health signals.
    |
    | Supported Values:
    | - 'CORE_FROZEN_NO_POLICY': Core is active but no policies defined. Suppress alarms.
    | - 'POLICY_ACTIVE': Policy layer is active. Show full alerts.
    |
    */
    'phase' => 'CORE_FROZEN_NO_POLICY',

    'is_policy_active' => function() {
        return config('seo.phase') === 'POLICY_ACTIVE';
    }
];
