<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'ai_monthly_credit')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('ai_monthly_credit')->default(0)->after('password');
            });
        }

        if (! Schema::hasTable('ai_credit_ledgers')) {
            Schema::create('ai_credit_ledgers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('old_credit')->default(0);
                $table->unsignedInteger('new_credit')->default(0);
                $table->boolean('forced')->default(false);
                $table->string('note')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_usage_logs')) {
            Schema::create('ai_usage_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('action')->nullable();
                $table->unsignedInteger('credits')->default(1);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('seo_templates')) {
            Schema::create('seo_templates', function (Blueprint $table) {
                $table->id();
                $table->string('page_type');
                $table->string('model_type')->nullable();
                $table->string('title_template')->nullable();
                $table->text('description_template')->nullable();
                $table->string('h1_template')->nullable();
                $table->string('schema_type')->nullable();
                $table->longText('schema_template_json')->nullable();
                $table->string('default_robots')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('seo_schemas')) {
            Schema::create('seo_schemas', function (Blueprint $table) {
                $table->id();
                $table->morphs('schemaable');
                $table->string('schema_type');
                $table->string('schema_name')->nullable();
                $table->longText('schema_json')->nullable();
                $table->boolean('is_auto_generated')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_schemas');
        Schema::dropIfExists('seo_templates');
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('ai_credit_ledgers');
        if (Schema::hasColumn('users', 'ai_monthly_credit')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('ai_monthly_credit');
            });
        }
    }
};
