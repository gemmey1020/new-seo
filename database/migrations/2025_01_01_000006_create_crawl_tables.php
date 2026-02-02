<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sitemap Sources
        Schema::create('sitemap_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('sitemap_url');
            $table->string('status')->default('pending'); // pending, processing, active, error
            $table->timestamp('last_fetched_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        // Crawl Runs
        Schema::create('crawl_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('mode'); // sitemap, seed, full
            $table->string('user_agent')->default('DefaultBot/1.0');
            $table->string('status')->default('running'); // running, completed, warrior
            $table->integer('pages_discovered')->default(0);
            $table->integer('pages_crawled')->default(0);
            $table->integer('errors_count')->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        // Crawl Logs
        Schema::create('crawl_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained()->nullOnDelete(); // Can be null if page not yet created? Spec says "crawl logs must belong to a crawl_run". But usually logs create pages. Or logs are just ephemeral? Spec says "pages... last_crawled_at". Logs should link to page if possible.
            $table->foreignId('crawl_run_id')->constrained()->cascadeOnDelete();
            
            $table->string('user_agent')->nullable();
            $table->integer('status_code')->nullable();
            $table->integer('response_ms')->nullable();
            $table->integer('bytes')->nullable();
            $table->string('content_type')->nullable();
            $table->text('final_url')->nullable(); // In case of redirects
            $table->boolean('blocked_by_robots')->default(false);
            $table->boolean('blocked_by_meta')->default(false);
            $table->text('notes')->nullable();
            
            $table->timestamp('crawled_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_logs');
        Schema::dropIfExists('crawl_runs');
        Schema::dropIfExists('sitemap_sources');
    }
};
