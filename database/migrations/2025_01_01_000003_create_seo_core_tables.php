<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pages
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('url'); // Relative or Absolute? Spec implies unique per site. Assuming path/relative mostly but stored as full or relative.
            // Spec says: "url must be unique per site".
            // Typically storage is relative path, but let's stick to 'url' as per spec.
            $table->string('path')->index();
            $table->string('canonical_url')->nullable();
            $table->string('page_type')->nullable(); // e.g. 'home', 'article', 'product'
            $table->string('index_status')->default('unknown'); // indexable, noindex, etc.
            $table->integer('http_status_last')->nullable();
            $table->integer('depth_level')->default(0);
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'url']);
        });

        // SEO Meta (1:1 with Page)
        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete()->unique(); // 1:1 constraint
            $table->string('title')->nullable();
            $table->text('description')->nullable(); // Text purely for length safety
            $table->string('robots')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image_url')->nullable();
            $table->string('twitter_card')->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image_url')->nullable();
            $table->timestamps();
        });

        // SEO Meta Versions
        Schema::create('seo_meta_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_meta_id')->constrained('seo_meta')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Snapshot of data
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('robots')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image_url')->nullable();
            $table->string('twitter_card')->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image_url')->nullable();
            
            $table->string('change_note')->nullable();
            $table->timestamp('created_at')->useCurrent(); // Immutable history
        });

        // Schemas
        Schema::create('schemas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('schema_type'); // e.g., 'Article', 'Product'
            $table->longText('json_ld');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_validated')->default(false);
            $table->string('validation_provider')->nullable();
            $table->text('validation_errors')->nullable();
            $table->timestamp('last_validated_at')->nullable();
            $table->timestamps();
        });

        // Schema Versions
        Schema::create('schema_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schema_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('json_ld');
            $table->string('change_note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schema_versions');
        Schema::dropIfExists('schemas');
        Schema::dropIfExists('seo_meta_versions');
        Schema::dropIfExists('seo_meta');
        Schema::dropIfExists('pages');
    }
};
