<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// HUD Routes
Route::get('/hud/state', [\App\Http\Controllers\Api\HudController::class, 'state']);
Route::get('/hud/simulation', [\App\Http\Controllers\Api\HudController::class, 'simulate']);
Route::post('/hud/commit', [\App\Http\Controllers\Api\HudController::class, 'commit']);

// API v1 Routes
Route::prefix('v1')->middleware(['web', 'auth'])->group(function () {
    Route::apiResource('sites', \App\Http\Controllers\Api\V1\SiteController::class);
    
    // Nested Site Resources
    Route::prefix('sites/{site}')->group(function () {
        // Pages Module
        Route::get('pages', [\App\Http\Controllers\Api\V1\PageController::class, 'index']);
        Route::post('pages', [\App\Http\Controllers\Api\V1\PageController::class, 'store']);
        Route::get('pages/{page}', [\App\Http\Controllers\Api\V1\PageController::class, 'show']);
        Route::put('pages/{page}', [\App\Http\Controllers\Api\V1\PageController::class, 'update']);
        Route::patch('pages/{page}', [\App\Http\Controllers\Api\V1\PageController::class, 'update']);
        Route::delete('pages/{page}', [\App\Http\Controllers\Api\V1\PageController::class, 'destroy']);
        Route::post('pages/import-sitemap', [\App\Http\Controllers\Api\V1\PageController::class, 'importSitemap']);
        
        // Audits Module
        Route::get('audits', [\App\Http\Controllers\Api\V1\AuditController::class, 'index']);
        Route::put('audits/{audit}', [\App\Http\Controllers\Api\V1\AuditController::class, 'update']);
        Route::patch('audits/{audit}', [\App\Http\Controllers\Api\V1\AuditController::class, 'update']);
        Route::post('audits/run', [\App\Http\Controllers\Api\V1\AuditController::class, 'run']);
        Route::get('audits/rules', [\App\Http\Controllers\Api\V1\AuditController::class, 'rulesIndex']);
        Route::put('audits/rules/{rule}', [\App\Http\Controllers\Api\V1\AuditController::class, 'ruleUpdate']);
        
        // Links Module
        Route::get('links', [\App\Http\Controllers\Api\V1\LinkController::class, 'index']);
        Route::post('links/rebuild', [\App\Http\Controllers\Api\V1\LinkController::class, 'rebuild']);
        Route::get('links/orphans', [\App\Http\Controllers\Api\V1\LinkController::class, 'orphans']);
        
        // Tasks Module
        Route::get('tasks', [\App\Http\Controllers\Api\V1\TaskController::class, 'index']);
        Route::post('tasks', [\App\Http\Controllers\Api\V1\TaskController::class, 'store']);
        Route::put('tasks/{task}', [\App\Http\Controllers\Api\V1\TaskController::class, 'update']);
        Route::patch('tasks/{task}', [\App\Http\Controllers\Api\V1\TaskController::class, 'update']);
        Route::post('tasks/{task}/comments', [\App\Http\Controllers\Api\V1\TaskController::class, 'storeComment']);
        
        // Crawl Monitor Module
        Route::get('crawl/runs', [\App\Http\Controllers\Api\V1\CrawlController::class, 'index']);
        Route::post('crawl/runs', [\App\Http\Controllers\Api\V1\CrawlController::class, 'store']);
        Route::get('crawl/logs', [\App\Http\Controllers\Api\V1\CrawlController::class, 'logs']);
        
        // Health Module (A.1 - Critical Path Unblock)
        Route::get('health', [\App\Http\Controllers\Api\V1\HealthController::class, 'show']);
        Route::get('health/drift', [\App\Http\Controllers\Api\V1\HealthController::class, 'drift']);
        Route::get('health/readiness', [\App\Http\Controllers\Api\V1\HealthController::class, 'readiness']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
