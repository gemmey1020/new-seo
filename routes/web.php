<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Access Control: Middleware 'auth' ensures login.
| Data Access: Views use JS to fetch /api/v1 endpoints.
|
*/

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/dashboard', function () {
    return redirect('/sites');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::view('/profile', 'profile.edit')->name('profile.edit');
    // ... default breeze routes ...

    // SEO Control Panel Routes
    Route::view('/sites', 'sites.index')->name('sites.index');
    
    Route::prefix('sites/{site}')->group(function () {
        Route::view('/', 'sites.overview')->name('sites.overview');
        
        Route::view('/pages', 'sites.pages.index')->name('sites.pages.index');
        Route::view('/pages/{page}', 'sites.pages.show')->name('sites.pages.show');
        Route::view('/pages/{page}/meta', 'sites.meta.edit')->name('sites.meta.edit');
        
        Route::view('/audits', 'sites.audits.index')->name('sites.audits.index');
        Route::view('/links', 'sites.links.index')->name('sites.links.index');
        Route::view('/schemas', 'sites.schemas.index')->name('sites.schemas.index');
        
        Route::view('/crawl', 'sites.crawl.index')->name('sites.crawl.index');
        Route::view('/crawl/{run}', 'sites.crawl.show')->name('sites.crawl.show'); // UI View for Run Details
        
        Route::view('/tasks', 'sites.tasks.board')->name('sites.tasks.board');
    });
});

// HUD Route
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/hud', function () {
        return view('hud');
    })->name('hud');
});

require __DIR__.'/auth.php';
