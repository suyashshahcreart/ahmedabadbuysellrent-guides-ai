<?php

namespace App\Jobs;

use App\Models\AiSetting;
use App\Models\AiTopicQueue;
use App\Services\Ai\DuplicateContentException;
use App\Services\Ai\GenerationException;
use App\Services\Ai\GuideDraftWriter;
use App\Services\Ai\GuideGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateGuideDraftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public int $tries = 1;

    public function __construct(public int $topicId, public ?int $userId = null) {}

    public function handle(GuideGenerator $generator, GuideDraftWriter $writer): void
    {
        $topic = AiTopicQueue::query()->with(['angle', 'geoNode', 'category'])->find($this->topicId);
        if (! $topic) {
            return;
        }

        $settings = AiSetting::current();
        $generated = [];

        try {
            $generated = $generator->generate($topic, $settings);
            $writer->write($topic, $settings, $generated, $this->userId);
        } catch (DuplicateContentException $e) {
            $writer->markFailed($topic, $e->getMessage(), $generated, $this->userId, true);
            Log::info('AI duplicate skipped: '.$e->getMessage());
        } catch (GenerationException $e) {
            $writer->markFailed($topic, $e->getMessage(), $e->context, $this->userId, false);
            Log::error('AI generation failed: '.$e->getMessage(), ['topic_id' => $topic->id]);
            throw $e;
        } catch (Throwable $e) {
            $writer->markFailed($topic, $e->getMessage(), $generated, $this->userId, false);
            Log::error('AI generation failed: '.$e->getMessage(), ['topic_id' => $topic->id]);
            throw $e;
        }
    }
}
