<?php

namespace App\Services\Ai;

class AiTopicQueueResult
{
    public function __construct(
        public int $topicId,
        public int $runId,
        public string $status = 'queued', // queued|busy|waiting|skipped
        public ?string $message = null,
    ) {}
}
