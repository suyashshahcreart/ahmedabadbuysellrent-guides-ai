<?php

namespace App\Services\Ai;

use App\Jobs\GenerateGuideDraftJob;
use App\Models\AiGenerationRun;
use App\Models\AiSetting;
use App\Models\AiTopicQueue;
use App\Models\Article;
use Illuminate\Support\Facades\Log;

class AiScheduler
{
    public function __construct(private TopicPlanner $planner) {}

    /**
     * @param  bool  $forceImmediate  Admin Start: ignore wait clock and queue now.
     * @param  int|null  $preferRunId  Queue this run first (new Start), then other open runs.
     */
    public function tick(bool $forceImmediate = false, ?int $preferRunId = null): ?AiTopicQueueResult
    {
        $settings = AiSetting::current();
        if (! $settings->enabled) {
            return new AiTopicQueueResult(0, 0, 'skipped', 'Automation is paused.');
        }

        $totalAi = Article::query()->where('source', 'ai')->count();
        if ($totalAi >= (int) $settings->target_total) {
            $settings->enabled = false;
            $settings->next_tick_at = null;
            $settings->save();
            $this->completeOpenRunsThatMetTarget();

            return new AiTopicQueueResult(0, 0, 'skipped', 'Global target reached ('.$totalAi.'/'.$settings->target_total.').');
        }

        if (! $forceImmediate && $settings->next_tick_at && $settings->next_tick_at->isFuture()) {
            return new AiTopicQueueResult(
                0,
                (int) ($settings->active_run_id ?: 0),
                'waiting',
                'Next cycle at '.$settings->next_tick_at->format('Y-m-d H:i:s').'.'
            );
        }

        $this->reopenIncompleteRuns();
        $openRuns = $this->openRuns($preferRunId);
        if ($openRuns->isEmpty()) {
            $openRuns = collect([$this->beginNewSession($settings)]);
        }

        $queued = [];
        foreach ($openRuns as $run) {
            if ($run->hasMetTarget()) {
                $run->refreshLifecycleStatus();
                continue;
            }
            if ($this->isBusy($run)) {
                continue;
            }

            $result = $this->queueForRun($run, $settings);
            if ($result) {
                $queued[] = $result;
            }

            // Respect global article cap mid-loop.
            if (Article::query()->where('source', 'ai')->count() >= (int) $settings->target_total) {
                break;
            }
        }

        if ($queued !== []) {
            $settings->last_tick_at = now();
            $settings->next_tick_at = now()->addMinutes(AiSetting::intervalMinutes($settings->interval));
            if ($preferRunId) {
                $settings->active_run_id = $preferRunId;
            } elseif (! $settings->active_run_id) {
                $settings->active_run_id = $queued[0]->runId;
            }
            $settings->save();

            $ids = collect($queued)->pluck('topicId')->implode(', #');

            return new AiTopicQueueResult(
                $queued[0]->topicId,
                $queued[0]->runId,
                'queued',
                count($queued).' post(s) queued (#'.$ids.'). Next cycle: '.$settings->next_tick_at->format('Y-m-d H:i:s')
            );
        }

        $busyCount = $openRuns->filter(fn (AiGenerationRun $r) => $this->isBusy($r))->count();
        if ($busyCount > 0) {
            return new AiTopicQueueResult(
                0,
                (int) ($settings->active_run_id ?: 0),
                'busy',
                $busyCount.' run(s) already generating. Next cycle: '.
                optional($settings->next_tick_at)?->format('Y-m-d H:i:s')
            );
        }

        return new AiTopicQueueResult(0, (int) ($settings->active_run_id ?: 0), 'skipped', 'No open runs needed a new post.');
    }

    /**
     * Open a new history run without closing older runs.
     * Older runs stay active until their own target is finished.
     */
    public function beginNewSession(AiSetting $settings): AiGenerationRun
    {
        $this->reopenIncompleteRuns();

        $remainingGlobal = max(0, (int) $settings->target_total - Article::query()->where('source', 'ai')->count());
        $run = AiGenerationRun::query()->create([
            'status' => 'active',
            'interval_snapshot' => $settings->interval,
            'settings_snapshot' => $settings->toSnapshot(),
            'target' => max(1, $remainingGlobal),
            'started_at' => now(),
        ]);

        $settings->active_run_id = $run->id;
        $settings->save();

        return $run;
    }

    public function ensureRun(AiSetting $settings): AiGenerationRun
    {
        if ($settings->active_run_id) {
            $run = AiGenerationRun::query()->find($settings->active_run_id);
            if ($run && ! $run->hasMetTarget() && $run->status !== 'paused' && $run->status !== 'failed') {
                return $run;
            }
        }

        $existing = AiGenerationRun::query()->open()->latest('id')->first();
        if ($existing) {
            $settings->active_run_id = $existing->id;
            $settings->save();

            return $existing;
        }

        return $this->beginNewSession($settings);
    }

    public function pauseAllOpenRuns(): void
    {
        AiGenerationRun::query()
            ->whereIn('status', ['pending', 'active', 'running'])
            ->whereColumn('completed', '<', 'target')
            ->update(['status' => 'paused', 'running' => 0]);
    }

    public function resumePausedRuns(): void
    {
        AiGenerationRun::query()
            ->where('status', 'paused')
            ->whereColumn('completed', '<', 'target')
            ->update(['status' => 'active', 'finished_at' => null]);
    }

    public function syncAllRunStatuses(): void
    {
        $this->reopenIncompleteRuns();
        AiGenerationRun::query()->orderBy('id')->each(function (AiGenerationRun $run) {
            $run->refreshLifecycleStatus();
        });
    }

    private function openRuns(?int $preferRunId = null)
    {
        $runs = AiGenerationRun::query()
            ->open()
            ->orderBy('id')
            ->get();

        if ($preferRunId) {
            return $runs->sortBy(fn (AiGenerationRun $r) => (int) $r->id === (int) $preferRunId ? 0 : 1)->values();
        }

        return $runs->values();
    }

    private function reopenIncompleteRuns(): void
    {
        // Only reopen runs that were wrongly marked completed before their target finished.
        AiGenerationRun::query()
            ->where('status', 'completed')
            ->whereColumn('completed', '<', 'target')
            ->where('target', '>', 0)
            ->update([
                'status' => 'active',
                'finished_at' => null,
            ]);
    }

    private function queueForRun(AiGenerationRun $run, AiSetting $settings): ?AiTopicQueueResult
    {
        try {
            $topic = $this->planner->plan($run);
        } catch (\Throwable $e) {
            Log::warning('AI topic planning failed for run #'.$run->id.': '.$e->getMessage());
            $run->incrementCounter('failed');

            return null;
        }

        $topic->status = 'running';
        $topic->attempts = (int) $topic->attempts + 1;
        $topic->save();

        $run->incrementCounter('queued');
        $run->incrementCounter('running');
        $run->status = 'running';
        $run->started_at = $run->started_at ?: now();
        $run->finished_at = null;
        $run->save();

        GenerateGuideDraftJob::dispatch($topic->id)->onQueue('ai-generate');

        return new AiTopicQueueResult($topic->id, $run->id, 'queued');
    }

    private function isBusy(AiGenerationRun $run): bool
    {
        if ((int) $run->running > 0) {
            return true;
        }

        return AiTopicQueue::query()
            ->where('run_id', $run->id)
            ->where('status', 'running')
            ->exists();
    }

    private function completeOpenRunsThatMetTarget(): void
    {
        AiGenerationRun::query()
            ->whereIn('status', ['pending', 'active', 'running'])
            ->get()
            ->each(function (AiGenerationRun $run) {
                $run->refreshLifecycleStatus();
            });
    }

    private function completeActiveRun(AiSetting $settings): void
    {
        $this->completeOpenRunsThatMetTarget();
        $settings->active_run_id = null;
        $settings->save();
    }
}
