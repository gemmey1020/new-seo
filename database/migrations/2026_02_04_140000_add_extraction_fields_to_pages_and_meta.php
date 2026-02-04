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
        Schema::table('pages', function (Blueprint $table) {
            $table->integer('h1_count')->default(0)->after('http_status_last');
            $table->integer('image_count')->default(0)->after('h1_count');
            $table->unsignedBigInteger('content_bytes')->nullable()->after('image_count');
        });

        Schema::table('seo_meta', function (Blueprint $table) {
            $table->string('robots_header')->nullable()->after('robots');
            $table->text('canonical_extracted')->nullable()->after('canonical_override'); // text in case of long URLs
            $table->string('h1_first_text')->nullable()->after('canonical_extracted');
            $table->json('images_sample_json')->nullable()->after('h1_first_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['h1_count', 'image_count', 'content_bytes']);
        });

        Schema::table('seo_meta', function (Blueprint $table) {
            $table->dropColumn(['robots_header', 'canonical_extracted', 'h1_first_text', 'images_sample_json']);
        });
    }
};
