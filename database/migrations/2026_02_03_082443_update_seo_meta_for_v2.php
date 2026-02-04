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
        Schema::table('seo_meta', function (Blueprint $table) {
            $table->string('canonical_override')->nullable()->after('robots');
            $table->string('index_status')->nullable()->after('robots'); // explicit 'indexed'/'noindex'
        });

        Schema::table('seo_meta_versions', function (Blueprint $table) {
            $table->string('canonical_override')->nullable()->after('robots');
            $table->string('index_status')->nullable()->after('robots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
