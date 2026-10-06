<?php

namespace App\Services\Ai;

use App\Models\AiAngle;
use App\Models\AiGenerationRun;
use App\Models\AiGeoNode;
use App\Models\AiTopicQueue;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Support\Str;

class TopicPlanner
{
    public function __construct(private StructureResolver $structures) {}

    public function plan(AiGenerationRun $run): AiTopicQueue
    {
        $angles = AiAngle::query()->active()->orderBy('sort_order')->get();
        if ($angles->isEmpty()) {
            throw new \RuntimeException('No active AI angles. Run guides:seed-ai-automation.');
        }

        for ($attempt = 0; $attempt < 40; $attempt++) {
            $angle = $angles->random();
            $geo = $this->pickGeoForAngle($angle);
            if (! $geo) {
                continue;
            }

            $titleHint = $this->fillPattern($angle->title_pattern, $geo, $angle);
            $seedKey = hash('sha256', implode('|', [
                $angle->key,
                $geo->type,
                $geo->slug,
                Str::slug($titleHint),
            ]));

            if (AiTopicQueue::query()->where('seed_key', $seedKey)->exists()) {
                continue;
            }
            if (Article::query()->where('ai_seed_key', $seedKey)->exists()) {
                continue;
            }
            if (Article::query()->where('title', $titleHint)->exists()) {
                continue;
            }

            $category = $this->resolveCategory($angle);
            $template = $this->structures->forAngle($angle);
            $keywords = $this->buildKeywords($geo, $angle, $titleHint);

            return AiTopicQueue::query()->create([
                'run_id' => $run->id,
                'seed_key' => $seedKey,
                'topic' => $titleHint,
                'title_hint' => $titleHint,
                'category_id' => $category?->id,
                'geo_node_id' => $geo->id,
                'angle_id' => $angle->id,
                'structure_key' => $template->key,
                'keywords' => $keywords,
                'payload' => [
                    'geo' => ['id' => $geo->id, 'type' => $geo->type, 'name' => $geo->name, 'slug' => $geo->slug],
                    'angle' => ['key' => $angle->key, 'audience' => $angle->audience, 'intent' => $angle->intent, 'brief' => $angle->brief],
                    'structure_key' => $template->key,
                ],
                'status' => 'pending',
            ]);
        }

        throw new \RuntimeException('Could not invent a unique topic seed after many attempts.');
    }

    private function pickGeoForAngle(AiAngle $angle): ?AiGeoNode
    {
        $type = match (true) {
            str_contains($angle->title_pattern, '{road}') => 'road',
            str_contains($angle->title_pattern, '{landmark}') => 'landmark',
            default => 'area',
        };

        return AiGeoNode::query()->active()->ofType($type)->inRandomOrder()->first();
    }

    private function fillPattern(string $pattern, AiGeoNode $geo, AiAngle $angle): string
    {
        $replacements = [
            '{area}' => $geo->type === 'area' ? $geo->name : ($geo->name),
            '{road}' => $geo->name,
            '{landmark}' => $geo->name,
            '{zone}' => $geo->name,
        ];

        return trim(strtr($pattern, $replacements));
    }

    private function resolveCategory(AiAngle $angle): ?Category
    {
        if ($angle->category_slug) {
            $cat = Category::query()->where('slug', $angle->category_slug)->first();
            if ($cat) {
                return $cat;
            }
        }

        return Category::query()->where('slug', 'areas')->first()
            ?? Category::query()->orderBy('id')->first();
    }

    private function buildKeywords(AiGeoNode $geo, AiAngle $angle, string $titleHint): array
    {
        $primary = Str::lower($geo->name.' ahmedabad');
        $local = [
            $geo->name,
            $geo->name.' Ahmedabad',
            'flats in '.$geo->name,
            'buy flat in '.$geo->name,
        ];

        return [
            'primary' => $primary,
            'local' => $local,
            'audience' => $angle->audience,
            'intent' => $angle->intent,
            'title_hint' => $titleHint,
            'long_tail_suggestions' => [
                'is '.$geo->name.' good for buying a flat',
                'best areas near '.$geo->name.' Ahmedabad',
                $geo->name.' property price trends',
                'RERA flats in '.$geo->name,
            ],
        ];
    }
}
