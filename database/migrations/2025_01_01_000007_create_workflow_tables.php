<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tasks
        Schema::create('seo_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('audit_id')->nullable()->constrained('seo_audits')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users');
            
            $table->string('title');
            $table->text('details')->nullable();
            $table->string('priority')->default('medium'); // low, medium, high, critical
            $table->string('status')->default('todo'); // todo, doing, blocked, done
            
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['site_id', 'status']);
        });

        // Comments
        Schema::create('seo_task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('seo_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_task_comments');
        Schema::dropIfExists('seo_tasks');
    }
};
