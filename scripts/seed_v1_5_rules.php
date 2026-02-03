<?php

use App\Models\Audit\AuditRule;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Seeding Phase 1.5 Audit Rules...\n";

$rules = [
    [
        'key' => 'canonical_mismatch',
        'title' => 'Canonical URL Mismatch',
        'category' => 'compliance',
        'severity_default' => 'high',
        'is_active' => true,
        'config_json' => []
    ],
    [
        'key' => 'heading_hierarchy',
        'title' => 'Invalid Heading Hierarchy',
        'category' => 'structure',
        'severity_default' => 'medium',
        'is_active' => true,
        'config_json' => []
    ],
    [
        'key' => 'schema_error',
        'title' => 'Structured Data Error',
        'category' => 'compliance',
        'severity_default' => 'high',
        'is_active' => true,
        'config_json' => []
    ]
];

foreach ($rules as $r) {
    AuditRule::updateOrCreate(
        ['key' => $r['key']],
        $r
    );
    echo "Upserted: {$r['key']}\n";
}

echo "Done.\n";
