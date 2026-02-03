<?php

use App\Models\Auth\User;
use App\Models\Site\Site;
use App\Models\Site\SiteUser;
use App\Models\Auth\Role;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- VERIFY USER SITE RELATIONSHIP ---\n";

$user = User::where('email', 'admin@test.com')->first();
if (!$user) die("User not found.\n");

echo "User: {$user->id}\n";

// 1. Check existing sites
try {
    $sites = $user->sites;
    echo "Current Sites Count: " . $sites->count() . "\n";
} catch (\Exception $e) {
    echo "ERROR accessing sites: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Create a test site if none exist
if ($sites->isEmpty()) {
    echo "Creating Test Site...\n";
    $site = Site::create([
        'name' => 'Admin Sandbox',
        'domain' => 'sandbox.local',
        'status' => 'active'
    ]);
    
    SiteUser::create([
        'user_id' => $user->id,
        'site_id' => $site->id,
        'role_id' => 1, // Admin
        'status' => 'active'
    ]);
    
    $user->refresh();
    echo "New Sites Count: " . $user->sites->count() . "\n";
}

echo "Relationship OK.\n";
