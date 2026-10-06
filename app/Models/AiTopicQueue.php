<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiTopicQueue extends Model
{
    protected $table = 'ai_topic_queue';

    protected $fillable = [
        'run_id', 'seed_key', 'topic', 'title_hint', 'category_id', 'geo_node_id',
        'angle_id', 'structure_key', 'keywords', 'payload', 'status', 'article_id',
        'attempts', 'error',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'payload' => 'array',
        ];
    }

    public function run()
    {
        return $this->belongsTo(AiGenerationRun::class, 'run_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function geoNode()
    {
        return $this->belongsTo(AiGeoNode::class, 'geo_node_id');
    }

    public function angle()
    {
        return $this->belongsTo(AiAngle::class, 'angle_id');
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
