<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\SchemaController;
use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\CrawlController;
use App\Http\Controllers\Api\V1\LinkController;
use App\Http\Controllers\Api\V1\TaskController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Sites
    Route::get('/sites', [SiteController::class, 'index']);
    Route::post('/sites', [SiteController::class, 'store']); // Admin
    Route::get('/sites/{site}', [SiteController::class, 'show']);
    Route::put('/sites/{site}', [SiteController::class, 'update']); // Admin

    // Pages
    Route::prefix('sites/{site}')->group(function () {
        Route::get('/pages', [PageController::class, 'index']);
        Route::post('/pages', [PageController::class, 'store']);
        Route::post('/pages/import-sitemap', [PageController::class, 'importSitemap']);
        Route::get('/pages/{page}', [PageController::class, 'show']);
        Route::put('/pages/{page}', [PageController::class, 'update']);
        Route::delete('/pages/{page}', [PageController::class, 'destroy']); // Admin

        // Meta (Nested under Page)
        Route::get('/pages/{page}/meta', [MetaController::class, 'show']);
        Route::put('/pages/{page}/meta', [MetaController::class, 'update']);
        Route::get('/pages/{page}/meta/versions', [MetaController::class, 'versions']);

        // Schemas (Nested under Page)
        Route::get('/pages/{page}/schemas', [SchemaController::class, 'index']);
        Route::post('/pages/{page}/schemas', [SchemaController::class, 'store']);
        Route::put('/pages/{page}/schemas/{schema}', [SchemaController::class, 'update']);
        Route::post('/pages/{page}/schemas/{schema}/validate', [SchemaController::class, 'validate']);
        Route::get('/pages/{page}/schemas/{schema}/versions', [SchemaController::class, 'versions']);

        // Audits
        Route::post('/audits/run', [AuditController::class, 'run']);
        Route::get('/audits', [AuditController::class, 'index']);
        Route::put('/audits/{audit}', [AuditController::class, 'update']);
        
        // Audit Rules (Admin)
        Route::get('/audit-rules', [AuditController::class, 'rulesIndex']);
        Route::put('/audit-rules/{rule}', [AuditController::class, 'ruleUpdate']);

        // Crawl
        Route::post('/crawl/run', [CrawlController::class, 'store']);
        Route::get('/crawl/runs', [CrawlController::class, 'index']);
        Route::get('/crawl/logs', [CrawlController::class, 'logs']);

        // Links
        Route::post('/links/rebuild', [LinkController::class, 'rebuild']);
        Route::get('/links', [LinkController::class, 'index']);
        Route::get('/links/orphans', [LinkController::class, 'orphans']);

        // Tasks
        Route::get('/tasks', [TaskController::class, 'index']);
        Route::post('/tasks', [TaskController::class, 'store']);
        Route::put('/tasks/{task}', [TaskController::class, 'update']);
        Route::post('/tasks/{task}/comments', [TaskController::class, 'storeComment']);
    });
});
