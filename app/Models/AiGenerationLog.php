<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGenerationLog extends Model
{
    protected $fillable = [
        'run_id', 'topic_id', 'article_id', 'user_id', 'status', 'model',
        'input_tokens', 'output_tokens', 'word_count', 'prompt_summary',
        'raw_response', 'error',
    ];

    public function run()
    {
        return $this->belongsTo(AiGenerationRun::class, 'run_id');
    }

    public function topic()
    {
        return $this->belongsTo(AiTopicQueue::class, 'topic_id');
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
