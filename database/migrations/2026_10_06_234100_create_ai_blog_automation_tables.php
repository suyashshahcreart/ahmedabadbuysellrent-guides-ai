<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('articles')) {
            Schema::table('articles', function (Blueprint $table) {
                if (! Schema::hasColumn('articles', 'source')) {
                    $table->string('source', 20)->default('manual')->after('created_by');
                }
                if (! Schema::hasColumn('articles', 'ai_run_id')) {
                    $table->unsignedBigInteger('ai_run_id')->nullable()->after('source');
                }
                if (! Schema::hasColumn('articles', 'ai_seed_key')) {
                    $table->string('ai_seed_key', 64)->nullable()->unique()->after('ai_run_id');
                }
                if (! Schema::hasColumn('articles', 'content_hash')) {
                    $table->string('content_hash', 64)->nullable()->index()->after('ai_seed_key');
                }
            });
        }

        if (! Schema::hasTable('ai_settings')) {
            Schema::create('ai_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('enabled')->default(false);
                $table->string('interval', 32)->default('daily');
                $table->unsignedInteger('target_total')->default(500);
                $table->unsignedTinyInteger('publish_percent')->default(20);
                $table->string('model')->default('claude-haiku-4-5-20251001');
                $table->unsignedInteger('max_tokens')->default(8000);
                $table->unsignedInteger('min_words')->default(1200);
                $table->foreignId('default_category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->text('system_prompt_extra')->nullable();
                $table->text('research_notes')->nullable();
                $table->timestamp('last_tick_at')->nullable();
                $table->timestamp('next_tick_at')->nullable();
                $table->unsignedBigInteger('active_run_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_geo_nodes')) {
            Schema::create('ai_geo_nodes', function (Blueprint $table) {
                $table->id();
                $table->string('type', 32); // zone|area|road|landmark
                $table->string('name');
                $table->string('slug');
                $table->foreignId('parent_id')->nullable()->constrained('ai_geo_nodes')->nullOnDelete();
                $table->boolean('status')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['type', 'slug']);
            });
        }

        if (! Schema::hasTable('ai_angles')) {
            Schema::create('ai_angles', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->string('audience')->nullable();
                $table->string('intent', 32)->default('guide'); // guide|compare|area|budget|process
                $table->string('category_slug')->nullable();
                $table->string('title_pattern');
                $table->text('brief')->nullable();
                $table->boolean('status')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_structure_templates')) {
            Schema::create('ai_structure_templates', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->string('content_type', 32)->default('guide'); // guide|compare|area
                $table->json('sections');
                $table->json('keyword_rules');
                $table->text('notes')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_generation_runs')) {
            Schema::create('ai_generation_runs', function (Blueprint $table) {
                $table->id();
                $table->string('status', 32)->default('pending'); // pending|running|completed|paused|failed
                $table->string('interval_snapshot', 32)->nullable();
                $table->unsignedInteger('target')->default(0);
                $table->unsignedInteger('queued')->default(0);
                $table->unsignedInteger('running')->default(0);
                $table->unsignedInteger('completed')->default(0);
                $table->unsignedInteger('failed')->default(0);
                $table->unsignedInteger('duplicate_skipped')->default(0);
                $table->unsignedInteger('input_tokens')->default(0);
                $table->unsignedInteger('output_tokens')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_topic_queue')) {
            Schema::create('ai_topic_queue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('run_id')->nullable()->constrained('ai_generation_runs')->nullOnDelete();
                $table->string('seed_key', 64)->unique();
                $table->string('topic');
                $table->string('title_hint')->nullable();
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->foreignId('geo_node_id')->nullable()->constrained('ai_geo_nodes')->nullOnDelete();
                $table->foreignId('angle_id')->nullable()->constrained('ai_angles')->nullOnDelete();
                $table->string('structure_key')->nullable();
                $table->json('keywords')->nullable();
                $table->json('payload')->nullable();
                $table->string('status', 32)->default('pending'); // pending|running|done|failed|duplicate
                $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->text('error')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_generation_logs')) {
            Schema::create('ai_generation_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('run_id')->nullable()->constrained('ai_generation_runs')->nullOnDelete();
                $table->foreignId('topic_id')->nullable()->constrained('ai_topic_queue')->nullOnDelete();
                $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 32)->default('pending');
                $table->string('model')->nullable();
                $table->unsignedInteger('input_tokens')->default(0);
                $table->unsignedInteger('output_tokens')->default(0);
                $table->unsignedInteger('word_count')->default(0);
                $table->text('prompt_summary')->nullable();
                $table->longText('raw_response')->nullable();
                $table->text('error')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('articles') && Schema::hasColumn('articles', 'ai_run_id')) {
            Schema::table('articles', function (Blueprint $table) {
                // Soft FK without constrained() in case order issues on older dumps
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generation_logs');
        Schema::dropIfExists('ai_topic_queue');
        Schema::dropIfExists('ai_generation_runs');
        Schema::dropIfExists('ai_structure_templates');
        Schema::dropIfExists('ai_angles');
        Schema::dropIfExists('ai_geo_nodes');
        Schema::dropIfExists('ai_settings');

        if (Schema::hasTable('articles')) {
            Schema::table('articles', function (Blueprint $table) {
                foreach (['content_hash', 'ai_seed_key', 'ai_run_id', 'source'] as $col) {
                    if (Schema::hasColumn('articles', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
