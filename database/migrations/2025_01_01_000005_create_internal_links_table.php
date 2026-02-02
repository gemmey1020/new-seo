<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('to_page_id')->constrained('pages')->cascadeOnDelete();
            
            $table->string('anchor_text')->nullable();
            $table->boolean('is_nofollow')->default(false);
            $table->boolean('is_image_link')->default(false);
            $table->string('rel_attr')->nullable();
            
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamps();

            // Indexes for link graph queries
            $table->index(['site_id', 'to_page_id']); // "Inbound links"
            $table->index(['site_id', 'from_page_id']); // "Outbound links"
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_links');
    }
};
