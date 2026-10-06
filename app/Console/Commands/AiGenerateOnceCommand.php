<?php

namespace App\Console\Commands;

use App\Jobs\GenerateGuideDraftJob;
use App\Models\AiGenerationRun;
use App\Models\AiSetting;
use App\Services\Ai\TopicPlanner;
use Illuminate\Console\Command;

class AiGenerateOnceCommand extends Command
{
    protected $signature = 'guides:ai-generate-once {--sync : Run inline instead of queue} {--user= : User id for usage log}';

    protected $description = 'Plan and generate one AI guide article immediately (smoke test)';

    public function handle(TopicPlanner $planner): int
    {
        $settings = AiSetting::current();
        $run = AiGenerationRun::query()->create([
            'status' => 'running',
            'interval_snapshot' => 'manual',
            'target' => 1,
            'started_at' => now(),
            'queued' => 1,
            'running' => 1,
        ]);

        try {
            $topic = $planner->plan($run);
        } catch (\Throwable $e) {
            $run->status = 'failed';
            $run->failed = 1;
            $run->running = 0;
            $run->finished_at = now();
            $run->save();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $topic->status = 'running';
        $topic->attempts = 1;
        $topic->save();

        $userId = $this->option('user') ? (int) $this->option('user') : null;
        $job = new GenerateGuideDraftJob($topic->id, $userId);

        if ($this->option('sync')) {
            $this->info('Generating inline for: '.$topic->title_hint);
            try {
                dispatch_sync($job);
            } catch (\Throwable $e) {
                $this->error('Generation failed: '.$e->getMessage());

                return self::FAILURE;
            }
            $topic->refresh();
            if ($topic->article_id) {
                $this->info('Created article #'.$topic->article_id.' ('.$topic->status.')');

                return self::SUCCESS;
            }
            $this->warn('No article created. Status: '.$topic->status.' '.$topic->error);

            return self::FAILURE;
        }

        GenerateGuideDraftJob::dispatch($topic->id, $userId)->onQueue('ai-generate');
        $this->info("Queued topic #{$topic->id}: {$topic->title_hint}");

        return self::SUCCESS;
    }
}
