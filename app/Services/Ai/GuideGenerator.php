<?php

namespace App\Services\Ai;

use App\Models\AiSetting;
use App\Models\AiTopicQueue;
use App\Models\Article;
use Illuminate\Support\Str;
use RuntimeException;

class GuideGenerator
{
    public function __construct(
        private AnthropicClient $client,
        private StructureResolver $structures,
    ) {}

    /**
     * @return array{data:array,input_tokens:int,output_tokens:int,model:string,raw_text:string}
     */
    public function generate(AiTopicQueue $topic, AiSetting $settings): array
    {
        $topic->loadMissing(['angle', 'geoNode', 'category']);
        $angle = $topic->angle;
        $geo = $topic->geoNode;
        if (! $angle || ! $geo) {
            throw new RuntimeException('Topic missing angle or geo node.');
        }

        $template = $this->structures->forAngle($angle);
        $avoid = Article::query()
            ->where('category_id', $topic->category_id)
            ->latest('id')
            ->limit(30)
            ->pluck('title')
            ->all();

        $system = $this->systemPrompt($settings);
        $user = $this->userPrompt($topic, $template, $settings, $avoid);

        $result = $this->client->messages(
            $system,
            $user,
            $settings->model,
            $settings->max_tokens
        );

        $bundle = [
            'input_tokens' => $result['input_tokens'],
            'output_tokens' => $result['output_tokens'],
            'model' => $result['model'],
            'raw_text' => $result['text'],
            'prompt_summary' => Str::limit($topic->title_hint.' | '.$template->key.' | '.$geo->name, 240),
        ];

        try {
            $data = $this->parseJson($result['text']);
            $this->assertPayload($data, $settings->min_words);
        } catch (RuntimeException $e) {
            throw new GenerationException($e->getMessage(), $bundle, 0, $e);
        }

        return $bundle + ['data' => $data];
    }

    private function systemPrompt(AiSetting $settings): string
    {
        $extra = trim((string) $settings->system_prompt_extra);
        $research = trim((string) $settings->research_notes);

        $base = <<<'PROMPT'
You are an experienced Ahmedabad real-estate editor writing for local home buyers and renters.
Write like a sharp human journalist, not like ChatGPT. Vary sentence length. Use concrete local detail.
Avoid AI tells: no "In today's fast-paced world", "delve", "tapestry", "ever-evolving", "comprehensive guide", "in conclusion", emoji, or keyword stuffing.
Do not invent specific project launch prices, RERA numbers, or builder claims you cannot know. Prefer ranges, checklists, and "verify on site / RERA portal" language.
Output MUST be a single JSON object only. Do not wrap in markdown fences.
Prefer content_markdown (GitHub-flavored markdown with ## / ### headings, lists, tables). We convert it to HTML.
If you must send content_html instead, escape every double-quote inside strings as \". Prefer &quot; in HTML attributes/text. No img/iframe/script/style/SVG.
Write at least the minimum plain-text word count. Prefer 1300+ words so cleanup still clears the minimum.
PROMPT;

        if ($extra !== '') {
            $base .= "\n\nAdditional admin instructions:\n".$extra;
        }
        if ($research !== '') {
            $base .= "\n\nResearch / ranking notes from admin:\n".$research;
        }

        return $base;
    }

    private function userPrompt(AiTopicQueue $topic, $template, AiSetting $settings, array $avoid): string
    {
        $structure = $this->structures->promptBlock($template);
        $payload = [
            'title_hint' => $topic->title_hint,
            'topic' => $topic->topic,
            'category' => $topic->category?->name,
            'geo' => $topic->payload['geo'] ?? null,
            'angle' => $topic->payload['angle'] ?? null,
            'keywords' => $topic->keywords,
            'min_words' => $settings->min_words,
            'avoid_titles' => $avoid,
            'json_schema' => [
                'title' => 'string',
                'slug' => 'string kebab-case unique',
                'excerpt' => 'string 1-2 sentences',
                'content_markdown' => 'string markdown body with required ## sections (preferred)',
                'content_html' => 'optional HTML fallback if markdown omitted',
                'tag_names' => ['array of 4-8 short tags'],
                'seo' => [
                    'seo_title' => 'string <= 60 chars preferred',
                    'meta_description' => 'string <= 160 chars',
                    'seo_keywords' => 'comma separated',
                    'og_title' => 'string',
                    'og_description' => 'string',
                ],
                'faqs' => [['question' => 'string', 'answer' => 'string markdown or text']],
            ],
        ];

        return "Write one unique Ahmedabad real-estate article.\n\n"
            .$structure."\n\n"
            ."Brief JSON:\n".json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\n"
            ."Return JSON only matching json_schema. Prefer content_markdown. Title may improve on title_hint but must stay unique and local.";
    }

    private function parseJson(string $text): array
    {
        $clean = trim($text);
        if (preg_match('/```(?:json)?\s*(.*?)```/is', $clean, $m)) {
            $clean = trim($m[1]);
        }
        $start = strpos($clean, '{');
        $end = strrpos($clean, '}');
        if ($start !== false && $end !== false) {
            $clean = substr($clean, $start, $end - $start + 1);
        }

        $candidates = [
            $clean,
            $this->escapeBareQuotesInJsonStrings($clean),
        ];

        foreach ($candidates as $candidate) {
            $data = json_decode($candidate, true);
            if (is_array($data)) {
                return $this->normalizeContentFields($data);
            }
        }

        throw new RuntimeException('Claude did not return valid JSON.');
    }

    private function normalizeContentFields(array $data): array
    {
        $markdown = trim((string) ($data['content_markdown'] ?? ''));
        $html = trim((string) ($data['content_html'] ?? ''));

        if ($markdown !== '' && $html === '') {
            $data['content_html'] = Str::markdown($markdown);
        } elseif ($markdown !== '' && $html !== '') {
            // Prefer markdown when both present — more reliable from the model.
            $data['content_html'] = Str::markdown($markdown);
        }

        return $data;
    }

    /**
     * Escape unescaped double-quotes that appear inside JSON string literals.
     */
    private function escapeBareQuotesInJsonStrings(string $json): string
    {
        $out = '';
        $inString = false;
        $escaped = false;
        $len = strlen($json);

        for ($i = 0; $i < $len; $i++) {
            $ch = $json[$i];

            if (! $inString) {
                if ($ch === '"') {
                    $inString = true;
                }
                $out .= $ch;
                continue;
            }

            if ($escaped) {
                $out .= $ch;
                $escaped = false;
                continue;
            }

            if ($ch === '\\') {
                $out .= $ch;
                $escaped = true;
                continue;
            }

            if ($ch === '"') {
                $rest = substr($json, $i + 1);
                if (preg_match('/^\s*[,:}\]]/s', $rest)) {
                    $inString = false;
                    $out .= $ch;
                } else {
                    $out .= '\\"';
                }
                continue;
            }

            if ($ch === "\n") {
                $out .= '\\n';
                continue;
            }
            if ($ch === "\r") {
                $out .= '\\r';
                continue;
            }
            if ($ch === "\t") {
                $out .= '\\t';
                continue;
            }

            $out .= $ch;
        }

        return $out;
    }

    private function assertPayload(array $data, int $minWords): void
    {
        foreach (['title', 'slug', 'content_html'] as $key) {
            if (empty($data[$key]) || ! is_string($data[$key])) {
                throw new RuntimeException("Missing required field: {$key}");
            }
        }
        $words = str_word_count(trim(strip_tags($data['content_html'])));
        if ($words < $minWords) {
            throw new RuntimeException("Content too short: {$words} words (min {$minWords}).");
        }
    }
}
