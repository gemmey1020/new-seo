<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Models\Site\Site;
use App\Models\Auth\User;
use Laravel\Sanctum\Sanctum;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "| Endpoint | Result | Notes |\n";
echo "|---|---|---|\n";

function report(string $endpoint, bool $pass, string $note = '') {
    $res = $pass ? 'PASS' : 'FAIL';
    echo "| $endpoint | $res | $note |\n";
    if (!$pass) exit(1);
}

// Setup Context
$site = Site::firstOrFail();
$user = User::firstOrFail(); // Admin

// Mock Auth
Sanctum::actingAs($user, ['*']);

$baseUrl = 'http://localhost/api/v1/sites/' . $site->id;

// We use internal request simulation to bypass full HTTP stack if needed, 
// but Http::get needs a running server or valid URL. 
// Since we are CLI, we can dispatch requests internally via app()->handle().
// Or use Request::create.

function internalGet($uri, $user) {
    $request = \Illuminate\Http\Request::create($uri, 'GET');
    $request->headers->set('Accept', 'application/json');
    Sanctum::actingAs($user, ['*']);
    return app()->handle($request);
}

try {
    // 1. Health
    $res = internalGet("/api/v1/sites/{$site->id}/health", $user);
    $data = json_decode($res->getContent(), true);
    
    report(
        'GET /health', 
        $res->getStatusCode() === 200 && isset($data['score']), 
        "Score: " . ($data['score'] ?? 'N/A')
    );

    // 2. Drift
    $res = internalGet("/api/v1/sites/{$site->id}/health/drift", $user);
    $data = json_decode($res->getContent(), true);
    
    report(
        'GET /health/drift', 
        $res->getStatusCode() === 200 && isset($data['status']), 
        "Status: " . ($data['status'] ?? 'N/A')
    );

    // 3. Readiness
    $res = internalGet("/api/v1/sites/{$site->id}/health/readiness", $user);
    $data = json_decode($res->getContent(), true);
    
    report(
        'GET /health/readiness', 
        $res->getStatusCode() === 200 && is_bool($data['ready']), 
        "Ready: " . ($data['ready'] ? 'Yes' : 'No')
    );

} catch (\Exception $e) {
    echo "| ERROR | FAIL | " . $e->getMessage() . " |\n";
    exit(1);
}

echo "\nAPI VERIFIED.\n";
