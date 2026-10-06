<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            if (! Schema::hasColumn('tags', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('description');
            }
            if (! Schema::hasColumn('tags', 'status')) {
                $table->string('status', 20)->default('published')->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            if (Schema::hasColumn('tags', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('tags', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
};
