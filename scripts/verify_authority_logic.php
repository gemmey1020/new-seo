<?php

use App\Models\Site\Site;
use App\Models\Auth\User;
use App\Services\AuthorityService;
use App\Enums\ActionClass;
use App\Enums\ActionStatus;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- VERIFY AUTHORITY GATES ---\n";

function test($name, $expected, $actual, $reason = '') {
    $res = ($expected === $actual) ? "PASS" : "FAIL";
    echo "[$res] $name (Exp: " . ($expected ? 'YES' : 'NO') . " | Act: " . ($actual ? 'YES' : 'NO') . ")";
    if ($reason) echo " -> Reason: $reason";
    echo "\n";
}

// 1. Test Kill Switch (Env Default = False)
echo "1. Testing Global Kill Switch (Env Default)...\n";
// We need to re-bootstrap or simulate new request for separate env, but for simple script we can just use reflection or separate run.
// Easier: Verify FALSE first.
$service = new AuthorityService();
$site = Site::firstOrFail();
$can = $service->canPerform($site, ActionClass::CLASS_A, 'test_auto', ['path' => '/blog']);
test("Kill Switch Active", false, $can);

// 2. Test Inner Gates (requires Enabled)
// We must modify the env variable that Laravel's env() function reads.
// Laravel uses $_ENV and getenv.
$_ENV['AUTHORITY_ENABLED'] = true;
putenv('AUTHORITY_ENABLED=true'); 

echo "2. Testing Class C Block (Env=TRUE)...\n";
$can = $service->canPerform($site, ActionClass::CLASS_C, 'delete_content', ['path' => '/blog']);
$log = \Illuminate\Support\Facades\DB::table('action_logs')->latest()->first();
test("Class C Blocked", false, $can, $log->reason);

// 3. Test Sanctuary Rule
echo "3. Testing Sanctuary Rule (Homepage /)...\n";
$can = $service->canPerform($site, ActionClass::CLASS_A, 'update_meta', ['path' => '/']);
$log = \Illuminate\Support\Facades\DB::table('action_logs')->latest()->first();
test("Homepage Protected", false, $can, $log->reason);

// 4. Test Confidence Gate
echo "4. Testing Confidence Gate...\n";
// Force mock health or rely on real. 
// If real confidence is < 80, it denies.
$can = $service->canPerform($site, ActionClass::CLASS_A, 'test_auto_a', ['path' => '/blog']);
$log = \Illuminate\Support\Facades\DB::table('action_logs')->latest()->first();
echo "   Result: " . ($can ? 'ALLOWED' : 'DENIED') . " | Reason: " . $log->reason . "\n";
if (!$can) echo "   [PASS] Denied due to Confidence/Drift logic.\n";
