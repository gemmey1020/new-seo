<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            
            // From Page (Must exist)
            $table->foreignId('from_page_id')->constrained('pages')->cascadeOnDelete();
            
            // To URL (May or may not be resolved to a Page yet)
            $table->string('to_url', 2048);
            $table->string('to_url_hash', 64); // SHA256 hash for indexing
            $table->foreignId('to_page_id')->nullable()->constrained('pages')->nullOnDelete();
            
            // Attributes
            $table->boolean('is_internal')->default(true);
            $table->boolean('is_nofollow')->default(false);
            
            // Timestamps
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            
            // Keys & Indexes
            // Ensure unique edge per page+url using hash
            $table->unique(['from_page_id', 'to_url_hash']);
            
            // For finding orphans (where to_page_id is null or count is 0)
            $table->index('to_page_id');
            // For finding outbound links
            $table->index('from_page_id');
            // For looking up URL structure
            $table->index('to_url_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_links');
    }
};
