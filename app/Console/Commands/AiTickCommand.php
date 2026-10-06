<?php

namespace App\Console\Commands;

use App\Services\Ai\AiScheduler;
use Illuminate\Console\Command;

class AiTickCommand extends Command
{
    protected $signature = 'guides:ai-tick';

    protected $description = 'Advance AI blog automation schedule (1 article per due interval)';

    public function handle(AiScheduler $scheduler): int
    {
        $result = $scheduler->tick(false);
        if (! $result || $result->status !== 'queued') {
            $this->line($result?->message ?: 'No AI tick due.');

            return self::SUCCESS;
        }

        $this->info($result->message ?: "Dispatched topic #{$result->topicId} for run #{$result->runId}.");

        return self::SUCCESS;
    }
}
