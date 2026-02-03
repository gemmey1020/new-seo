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
        Schema::create('action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Null if system automated
            $table->string('action_class'); // A, B, C
            $table->string('action_type'); // e.g. "update_meta", "create_redirect"
            $table->string('status'); // ALLOWED, DENIED, ERROR
            $table->text('reason')->nullable(); // Why allowed/denied
            $table->json('payload')->nullable(); // What was attempted
            $table->json('snapshot')->nullable(); // Original state (for rollback)
            $table->timestamps();
            
            $table->index(['site_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_logs');
    }
};
