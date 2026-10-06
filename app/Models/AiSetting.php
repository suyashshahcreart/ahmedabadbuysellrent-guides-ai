<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    protected $fillable = [
        'enabled', 'interval', 'target_total', 'publish_percent', 'model', 'max_tokens',
        'min_words', 'default_category_id', 'system_prompt_extra', 'research_notes',
        'last_tick_at', 'next_tick_at', 'active_run_id',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'target_total' => 'integer',
            'publish_percent' => 'integer',
            'max_tokens' => 'integer',
            'min_words' => 'integer',
            'last_tick_at' => 'datetime',
            'next_tick_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        $row = static::query()->first();
        if ($row) {
            return $row;
        }

        return static::query()->create([
            'enabled' => false,
            'interval' => 'daily',
            'target_total' => 500,
            'publish_percent' => 20,
            'model' => config('services.anthropic.model', 'claude-haiku-4-5-20251001'),
            'max_tokens' => (int) config('services.anthropic.max_tokens', 8000),
            'min_words' => 1200,
        ]);
    }

    public function defaultCategory()
    {
        return $this->belongsTo(Category::class, 'default_category_id');
    }

    public function activeRun()
    {
        return $this->belongsTo(AiGenerationRun::class, 'active_run_id');
    }

    public static function intervalMinutes(string $interval): int
    {
        return match ($interval) {
            '2h' => 120,
            '4h' => 240,
            '8h' => 480,
            'daily' => 1440,
            'every_5_days' => 7200,
            'monthly' => 43200,
            default => 1440,
        };
    }

    public static function intervalOptions(): array
    {
        return [
            '2h' => 'Every 2 hours',
            '4h' => 'Every 4 hours',
            '8h' => 'Every 8 hours',
            'daily' => 'Every day',
            'every_5_days' => 'Every 5 days',
            'monthly' => 'Every month',
        ];
    }

    /**
     * Snapshot of live automation config for run history pages.
     */
    public function toSnapshot(): array
    {
        $this->loadMissing('defaultCategory');

        return [
            'interval' => $this->interval,
            'interval_label' => self::intervalOptions()[$this->interval] ?? $this->interval,
            'target_total' => (int) $this->target_total,
            'publish_percent' => (int) $this->publish_percent,
            'model' => $this->model,
            'max_tokens' => (int) $this->max_tokens,
            'min_words' => (int) $this->min_words,
            'default_category_id' => $this->default_category_id,
            'default_category' => $this->defaultCategory?->name,
            'system_prompt_extra' => $this->system_prompt_extra,
            'research_notes' => $this->research_notes,
            'structure_templates' => AiStructureTemplate::query()
                ->orderBy('id')
                ->get(['id', 'key', 'name', 'content_type', 'status', 'sections'])
                ->map(fn (AiStructureTemplate $t) => [
                    'id' => $t->id,
                    'key' => $t->key,
                    'name' => $t->name,
                    'content_type' => $t->content_type,
                    'status' => (bool) $t->status,
                    'sections_count' => count($t->sections ?? []),
                    'sections' => $t->sections ?? [],
                ])
                ->all(),
            'geo_pools' => [
                'zones' => AiGeoNode::query()->where('type', 'zone')->count(),
                'areas' => AiGeoNode::query()->where('type', 'area')->count(),
                'roads' => AiGeoNode::query()->where('type', 'road')->count(),
                'landmarks' => AiGeoNode::query()->where('type', 'landmark')->count(),
                'total' => AiGeoNode::query()->count(),
            ],
            'angles' => AiAngle::query()
                ->orderBy('sort_order')
                ->get(['id', 'key', 'name', 'intent', 'category_slug', 'status'])
                ->map(fn (AiAngle $a) => [
                    'id' => $a->id,
                    'key' => $a->key,
                    'name' => $a->name,
                    'intent' => $a->intent,
                    'category_slug' => $a->category_slug,
                    'status' => (bool) $a->status,
                ])
                ->all(),
            'captured_at' => now()->timezone(config('app.timezone', 'Asia/Kolkata'))->format('Y-m-d H:i:s'),
        ];
    }
}
