<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_meta', function (Blueprint $table) {
            $table->string('seo_keywords')->nullable()->after('robots');
            $table->foreignId('og_image_media_id')->nullable()->after('og_image')->constrained('media_assets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('seo_meta', function (Blueprint $table) {
            $table->dropConstrainedForeignId('og_image_media_id');
            $table->dropColumn('seo_keywords');
        });
    }
};
