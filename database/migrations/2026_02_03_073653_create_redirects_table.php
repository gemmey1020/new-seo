<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('from_url'); // Relative path e.g. /old-page
            $table->string('to_url')->nullable(); // Nullable for 410
            $table->string('type')->default('301'); // 301, 302, 410
            $table->string('status')->default('active'); // active, disabled
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null'); // Auditable
            $table->timestamps();

            // Strict Uniqueness: One redirect per source URL per site
            $table->unique(['site_id', 'from_url']);
            $table->index(['site_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
