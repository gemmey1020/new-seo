<?php

use App\Models\Site\Site;
use App\Models\Seo\Page;
use App\Models\Seo\SeoMeta;
use App\Models\Auth\User;
use App\Services\MetaService;
use App\Services\AuthorityService;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- VERIFY META CONTROLS ---\n";

$site = Site::firstOrFail();
$user = User::first(); 
$auth = new AuthorityService();
$service = new MetaService($auth);

// Setup: Ensure Homepage Exists
$home = Page::firstOrCreate(['site_id' => $site->id, 'path' => '/'], ['url' => $site->domain, 'http_status_last' => 200]);
// Setup: Regular Page
$page = Page::firstOrCreate(['site_id' => $site->id, 'path' => '/blog/meta-test'], ['url' => $site->domain.'/blog/meta-test', 'http_status_last' => 200]);

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
attempt("Update Meta (Lock Active)", function() use ($service, $page, $user) {
    $service->updateMeta($page, ['title' => 'New Title'], $user);
}, false, "Meta Update DENIED");

// 2. Bypass Lock for Safety Tests
$_ENV['AUTHORITY_ENABLED'] = true;
putenv('AUTHORITY_ENABLED=true');

// 3. Test Sanctuary Rule (Noindex on Home)
attempt("Set Homepage Noindex (Sanctuary)", function() use ($service, $home, $user) {
    $service->updateMeta($home, ['index_status' => 'noindex'], $user);
}, false, "SANCTUARY VIOLATION");

// 4. Test Valid Update & Versioning
// Bypass Lock using Mockery strictly for this test to prove MetaService logic works when Gate is Open.
attempt("Valid Update (Title Change)", function() use ($page, $user, $site) {
    // Create Mock Authority that says YES
    $mockAuth = Mockery::mock(AuthorityService::class);
    $mockAuth->shouldReceive('canPerform')->andReturn(true);
    
    // Inject Mock
    $serviceOk = new MetaService($mockAuth);
    $serviceOk->updateMeta($page, ['title' => 'Updated Title v1'], $user);
}, true);

// Verify DB
// Verify DB
$meta = SeoMeta::where('page_id', $page->id)->first();
if ($meta) {
    echo "Current Title: " . $meta->title . " (Exp: Updated Title v1)\n";
    echo "Versions Count: " . $meta->versions()->count() . "\n";
    
    // 5. Test Undo
    if ($meta->versions()->count() > 0) {
        $v1 = $meta->versions()->latest()->first();
        attempt("Undo Change", function() use ($v1, $user) {
             // Mock Auth for Undo
             $mockAuth = Mockery::mock(AuthorityService::class);
             $mockAuth->shouldReceive('canPerform')->andReturn(true);
             $serviceOk = new MetaService($mockAuth);
             $serviceOk->undoChange($v1->id, $user);
        }, true);
        
        $meta->refresh();
        echo "Restored Title: " . $meta->title . "\n";
        echo "Versions After Undo: " . $meta->versions()->count() . " (Should be +1)\n";
    }
} else {
    echo "[FAIL] Meta record NOT created.\n";
}
