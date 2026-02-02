<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit Rules
        Schema::create('audit_rules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'missing_title'
            $table->string('title');
            $table->string('category'); // e.g., 'meta', 'technical'
            $table->string('severity_default'); // 'critical', 'high', 'medium', 'low'
            $table->boolean('is_active')->default(true);
            $table->text('config_json')->nullable(); // Thresholds etc.
            $table->timestamps();
        });

        // SEO Audits (Findings)
        Schema::create('seo_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained()->cascadeOnDelete(); // Some findings might be site-wide? Spec implies pages findings mainly.
            $table->foreignId('rule_id')->constrained('audit_rules');
            
            $table->string('severity'); // Snapshot of severity at detection time
            $table->string('status')->default('open'); // open, acknowledged, fixed, wontfix
            $table->text('description')->nullable();
            $table->text('evidence_json')->nullable(); // Specific data like "Title length: 15"
            
            $table->timestamp('detected_at')->useCurrent();
            $table->timestamp('fixed_at')->nullable();
            $table->foreignId('fixed_by_user_id')->nullable()->constrained('users');
            
            $table->timestamps();

            // Index for efficiency
            $table->index(['site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_audits');
        Schema::dropIfExists('audit_rules');
    }
};
