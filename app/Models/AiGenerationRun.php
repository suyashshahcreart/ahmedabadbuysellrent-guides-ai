<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AiGenerationRun extends Model
{
    protected $fillable = [
        'status', 'interval_snapshot', 'settings_snapshot', 'target', 'queued', 'running', 'completed',
        'failed', 'duplicate_skipped', 'input_tokens', 'output_tokens',
        'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'settings_snapshot' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function snapshot(): array
    {
        return is_array($this->settings_snapshot) ? $this->settings_snapshot : [];
    }

    public function topics()
    {
        return $this->hasMany(AiTopicQueue::class, 'run_id');
    }

    public function logs()
    {
        return $this->hasMany(AiGenerationLog::class, 'run_id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'ai_run_id');
    }

    public function remaining(): int
    {
        return max(0, (int) $this->target - (int) $this->completed);
    }

    public function hasMetTarget(): bool
    {
        return (int) $this->target > 0 && (int) $this->completed >= (int) $this->target;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'active', 'running'])
            ->whereColumn('completed', '<', 'target');
    }

    public function incrementCounter(string $field, int $by = 1): void
    {
        $this->increment($field, $by);
        $this->refresh();
    }

    /**
     * Status rules:
     * - running = a topic job is in progress
     * - active  = target not finished, waiting/working in background
     * - completed = ONLY when completed >= target
     * - paused / failed = explicit states
     */
    public function refreshLifecycleStatus(): void
    {
        if ($this->status === 'paused' || $this->status === 'failed') {
            if ((int) $this->running > 0 && ! $this->topics()->where('status', 'running')->exists()) {
                $this->running = 0;
                $this->save();
            }

            return;
        }

        $hasLiveTopic = $this->topics()->where('status', 'running')->exists();
        if ((int) $this->running > 0 && ! $hasLiveTopic) {
            $this->running = 0;
        }

        if ($hasLiveTopic || (int) $this->running > 0) {
            if ($this->status !== 'running') {
                $this->status = 'running';
            }
            $this->finished_at = null;
            $this->save();

            return;
        }

        if ($this->hasMetTarget()) {
            $this->status = 'completed';
            $this->finished_at = $this->finished_at ?: now();
            $this->save();

            return;
        }

        // Still under target — stay active (even if another Start opened a newer run).
        $this->status = 'active';
        $this->finished_at = null;
        $this->save();
    }
}
