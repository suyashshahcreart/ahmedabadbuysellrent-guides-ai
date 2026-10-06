<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_generation_runs') && ! Schema::hasColumn('ai_generation_runs', 'settings_snapshot')) {
            Schema::table('ai_generation_runs', function (Blueprint $table) {
                $table->json('settings_snapshot')->nullable()->after('interval_snapshot');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_generation_runs') && Schema::hasColumn('ai_generation_runs', 'settings_snapshot')) {
            Schema::table('ai_generation_runs', function (Blueprint $table) {
                $table->dropColumn('settings_snapshot');
            });
        }
    }
};
