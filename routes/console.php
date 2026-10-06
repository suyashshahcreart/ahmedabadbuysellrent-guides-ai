<?php

use App\Console\Commands\AiTickCommand;
use App\Console\Commands\PublishScheduledCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(PublishScheduledCommand::class)->everyMinute();
Schedule::command(AiTickCommand::class)->everyMinute();
