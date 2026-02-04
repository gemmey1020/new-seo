<?php

use App\Models\Site\Site;
use App\Models\Redirect\Redirect;
use App\Models\Auth\User;
use App\Services\RedirectService;
use App\Services\AuthorityService;
use App\Enums\ActionClass;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- VERIFY REDIRECT MANAGER ---\n";

$site = Site::firstOrFail();
$user = User::first(); // Admin
$auth = new AuthorityService();
$service = new RedirectService($auth);

// Helper to assert
function attempt($name, $callback, $expectSuccess, $expectErrorPart = null) {
    echo "TEST: $name ... ";
    try {
        $callback();
        if ($expectSuccess) echo "[PASS] Success.\n";
        else echo "[FAIL] Unexpected Success.\n";
    } catch (\Exception $e) {
        if (!$expectSuccess) {
            if ($expectErrorPart && strpos($e->getMessage(), $expectErrorPart) !== false) {
                 echo "[PASS] Blocked: " . $e->getMessage() . "\n";
            } else {
                 echo "[FAIL] Wrong Error: " . $e->getMessage() . "\n";
            }
        } else {
            echo "[FAIL] Exception: " . $e->getMessage() . "\n";
        }
    }
}

// 1. Test Authority Lock (Env Default = False)
attempt("Create valid 301 (Lock Active)", function() use ($service, $site, $user) {
    $service->createRedirect($site, ['from_url' => '/old', 'to_url' => '/new'], $user);
}, false, "Authority is globally DISABLED");

// 2. Test Sanctuary Rule (Bypassing Lock for Test)
// Force Env bypass
$_ENV['AUTHORITY_ENABLED'] = true;
putenv('AUTHORITY_ENABLED=true');

attempt("Redirect Homepage / (Sanctuary)", function() use ($service, $site, $user) {
    $service->createRedirect($site, ['from_url' => '/', 'to_url' => '/new'], $user);
}, false, "SANCTUARY VIOLATION");

// 3. Test Loop Detection
attempt("Self Loop (/foo -> /foo)", function() use ($service, $site, $user) {
    $service->createRedirect($site, ['from_url' => '/loop', 'to_url' => '/loop'], $user);
}, false, "Self-redirect loop");

// 4. Test Valid Redirect (With Authority Enabled)
attempt("Valid Class B Write", function() use ($service, $site, $user) {
    $service->createRedirect($site, ['from_url' => '/legacy', 'to_url' => '/valid'], $user);
}, true);

// 5. Verify DB
$r = Redirect::where('site_id', $site->id)->where('from_url', '/legacy')->first();
if ($r) echo "[PASS] Redirect saved in DB.\n";
else echo "[FAIL] Redirect NOT in DB.\n";

// 6. Verify Log
$log = \Illuminate\Support\Facades\DB::table('action_logs')->latest()->first();
echo "Log Status: " . $log->status . " | Action: " . $log->action_type . "\n";
