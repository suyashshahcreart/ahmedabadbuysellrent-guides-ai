<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Page;
use Illuminate\Console\Command;

class PublishScheduledCommand extends Command
{
    protected $signature = 'guides:publish-scheduled';

    protected $description = 'Publish scheduled articles and pages';

    public function handle(): int
    {
        $n = 0;
        foreach ([Article::class, Page::class] as $class) {
            $class::query()->where('status', 'scheduled')
                ->where('scheduled_at', '<=', now())
                ->get()
                ->each(function ($m) use (&$n) {
                    $m->update(['status' => 'published', 'published_at' => $m->published_at ?: now()]);
                    $n++;
                });
        }
        $this->info("Published {$n} items.");

        return self::SUCCESS;
    }
}
