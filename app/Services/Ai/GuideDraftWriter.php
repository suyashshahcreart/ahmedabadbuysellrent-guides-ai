<?php

namespace App\Services\Ai;

use App\Models\AiGenerationLog;
use App\Models\AiGenerationRun;
use App\Models\AiSetting;
use App\Models\AiTopicQueue;
use App\Models\AiUsageLog;
use App\Models\Article;
use App\Models\Author;
use App\Models\Tag;
use App\Services\HtmlSanitizer;
use App\Services\SchemaSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class GuideDraftWriter
{
    public function __construct(private HtmlSanitizer $html) {}

    /**
     * @param  array{data:array,input_tokens:int,output_tokens:int,model:string,raw_text:string,prompt_summary?:string}  $generated
     */
    public function write(AiTopicQueue $topic, AiSetting $settings, array $generated, ?int $userId = null): Article
    {
        $data = $generated['data'];
        $title = trim((string) $data['title']);
        $slug = Str::slug((string) ($data['slug'] ?: $title));
        $content = $this->html->clean($this->normalizeAiHtml((string) $data['content_html']));
        $plain = trim(strip_tags($content));
        $hash = hash('sha256', preg_replace('/\s+/', ' ', Str::lower($plain)) ?? $plain);
        $wordCount = str_word_count($plain);

        if ($wordCount < $settings->min_words) {
            throw new RuntimeException("Content too short after sanitize: {$wordCount}");
        }

        if (Article::query()->where('title', $title)->exists()) {
            throw new DuplicateContentException('Duplicate title: '.$title);
        }
        if (Article::query()->where(function ($q) use ($slug) {
            $q->where('slug', $slug)->orWhere('path', 'guides/'.$slug.'/');
        })->exists()) {
            throw new DuplicateContentException('Duplicate slug: '.$slug);
        }
        if (Article::query()->where('content_hash', $hash)->exists()) {
            throw new DuplicateContentException('Duplicate content hash.');
        }
        if (Article::query()->where('ai_seed_key', $topic->seed_key)->exists()) {
            throw new DuplicateContentException('Duplicate seed key.');
        }

        $publish = random_int(1, 100) <= max(0, min(100, (int) $settings->publish_percent));
        $authorId = $this->nextAuthorId();

        return DB::transaction(function () use ($topic, $settings, $generated, $userId, $data, $title, $slug, $content, $hash, $wordCount, $publish, $authorId) {
            $article = new Article();
            $article->fill([
                'type' => 'guide',
                'title' => $title,
                'slug' => $slug,
                'path' => 'guides/'.$slug.'/',
                'excerpt' => Str::limit(trim((string) ($data['excerpt'] ?? '')), 500),
                'content' => $content,
                'category_id' => $topic->category_id,
                'author_id' => $authorId,
                'status' => $publish ? 'published' : 'draft',
                'published_at' => $publish ? now() : null,
                'reading_time' => $this->html->readingMinutes($content),
                'featured' => false,
                'trending' => false,
                'created_by' => $userId,
                'source' => 'ai',
                'ai_run_id' => $topic->run_id,
                'ai_seed_key' => $topic->seed_key,
                'content_hash' => $hash,
            ]);
            $article->save();

            $this->syncTags($article, (array) ($data['tag_names'] ?? []));
            $this->saveSeo($article, (array) ($data['seo'] ?? []), $title);
            $this->saveFaqs($article, (array) ($data['faqs'] ?? []));
            app(SchemaSync::class)->sync($article);

            $topic->status = 'done';
            $topic->article_id = $article->id;
            $topic->error = null;
            $topic->save();

            if ($topic->run_id) {
                /** @var AiGenerationRun|null $run */
                $run = AiGenerationRun::query()->find($topic->run_id);
                if ($run) {
                    $run->incrementCounter('completed');
                    $run->input_tokens = (int) $run->input_tokens + (int) $generated['input_tokens'];
                    $run->output_tokens = (int) $run->output_tokens + (int) $generated['output_tokens'];
                    if ($run->running > 0) {
                        $run->decrement('running');
                    }
                    $run->save();

                    $run->refreshLifecycleStatus();

                    $totalAi = Article::query()->where('source', 'ai')->count();
                    if ($totalAi >= (int) $settings->target_total) {
                        $settings->enabled = false;
                        $settings->next_tick_at = null;
                        $settings->save();
                    }
                }
            }

            AiGenerationLog::query()->create([
                'run_id' => $topic->run_id,
                'topic_id' => $topic->id,
                'article_id' => $article->id,
                'user_id' => $userId,
                'status' => 'done',
                'model' => $generated['model'] ?? $settings->model,
                'input_tokens' => (int) $generated['input_tokens'],
                'output_tokens' => (int) $generated['output_tokens'],
                'word_count' => $wordCount,
                'prompt_summary' => $generated['prompt_summary'] ?? $topic->title_hint,
                'raw_response' => Str::limit((string) ($generated['raw_text'] ?? ''), 20000),
            ]);

            if ($userId) {
                AiUsageLog::query()->create([
                    'user_id' => $userId,
                    'action' => 'ai.blog.generate',
                    'credits' => 1,
                    'meta' => [
                        'article_id' => $article->id,
                        'input_tokens' => $generated['input_tokens'],
                        'output_tokens' => $generated['output_tokens'],
                        'model' => $generated['model'] ?? $settings->model,
                    ],
                ]);
            }

            return $article->fresh(['category', 'author', 'tags']);
        });
    }

    public function markFailed(AiTopicQueue $topic, string $error, array $generated = [], ?int $userId = null, bool $duplicate = false): void
    {
        $topic->status = $duplicate ? 'duplicate' : 'failed';
        $topic->error = Str::limit($error, 2000);
        $topic->attempts = (int) $topic->attempts + 1;
        $topic->save();

        if ($topic->run_id) {
            $run = AiGenerationRun::query()->find($topic->run_id);
            if ($run) {
                if ($run->running > 0) {
                    $run->decrement('running');
                }
                $run->incrementCounter($duplicate ? 'duplicate_skipped' : 'failed');
                if (! empty($generated['input_tokens']) || ! empty($generated['output_tokens'])) {
                    $run->input_tokens = (int) $run->input_tokens + (int) ($generated['input_tokens'] ?? 0);
                    $run->output_tokens = (int) $run->output_tokens + (int) ($generated['output_tokens'] ?? 0);
                    $run->save();
                }
                $run->refreshLifecycleStatus();
            }
        }

        AiGenerationLog::query()->create([
            'run_id' => $topic->run_id,
            'topic_id' => $topic->id,
            'user_id' => $userId,
            'status' => $duplicate ? 'duplicate' : 'failed',
            'model' => $generated['model'] ?? null,
            'input_tokens' => (int) ($generated['input_tokens'] ?? 0),
            'output_tokens' => (int) ($generated['output_tokens'] ?? 0),
            'prompt_summary' => $generated['prompt_summary'] ?? $topic->title_hint,
            'raw_response' => isset($generated['raw_text']) ? Str::limit((string) $generated['raw_text'], 20000) : null,
            'error' => Str::limit($error, 2000),
        ]);
    }

    private function normalizeAiHtml(string $html): string
    {
        // Drop images from AI drafts (media is managed separately) and strip risky attrs.
        $html = preg_replace('/<img\b[^>]*>/i', '', $html) ?? $html;
        $html = preg_replace('/\s+loading=("|\')[^"\']*("|\')/i', '', $html) ?? $html;

        return $html;
    }

    private function nextAuthorId(): ?int
    {
        $editorSlugs = [
            'ananya-mehta', 'rohan-patel', 'kavya-shah', 'harsh-trivedi', 'isha-desai',
            'manav-joshi', 'priya-chauhan', 'devansh-raval', 'sneha-pandya', 'aarav-solanki',
        ];
        $authors = Author::query()->where('status', true)->whereIn('slug', $editorSlugs)->pluck('id');
        if ($authors->isEmpty()) {
            $authors = Author::query()->where('status', true)->pluck('id');
        }
        if ($authors->isEmpty()) {
            return null;
        }

        $lastId = Article::query()->where('source', 'ai')->latest('id')->value('author_id');
        $ids = $authors->values()->all();
        if (! $lastId) {
            return $ids[0];
        }
        $pos = array_search($lastId, $ids, true);
        if ($pos === false) {
            return $ids[0];
        }

        return $ids[($pos + 1) % count($ids)];
    }

    private function syncTags(Article $article, array $names): void
    {
        $ids = [];
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $slug = Str::slug($name);
            $tag = Tag::query()->where(function ($q) use ($slug, $name) {
                $q->where('slug', $slug)->orWhere('name', $name);
            })->first();
            if (! $tag) {
                $tag = Tag::create([
                    'name' => $name,
                    'slug' => $slug,
                    'status' => 'published',
                    'sort_order' => 0,
                ]);
            }
            $ids[] = $tag->id;
        }
        $article->tags()->sync(array_values(array_unique($ids)));
    }

    private function saveSeo(Article $article, array $seo, string $fallbackTitle): void
    {
        $payload = [
            'seo_title' => $seo['seo_title'] ?? $fallbackTitle,
            'meta_description' => $seo['meta_description'] ?? $article->excerpt,
            'seo_keywords' => $seo['seo_keywords'] ?? null,
            'og_title' => $seo['og_title'] ?? ($seo['seo_title'] ?? $fallbackTitle),
            'og_description' => $seo['og_description'] ?? ($seo['meta_description'] ?? $article->excerpt),
            'robots' => 'index,follow',
        ];
        $article->seo()->updateOrCreate([], array_filter($payload, fn ($v) => $v !== null && $v !== ''));
    }

    private function saveFaqs(Article $article, array $faqs): void
    {
        $article->faqs()->delete();
        foreach ($faqs as $i => $faq) {
            $question = trim((string) ($faq['question'] ?? ''));
            $answer = trim((string) ($faq['answer'] ?? ''));
            if ($question === '') {
                continue;
            }
            $article->faqs()->create([
                'question' => $question,
                'answer' => $this->html->clean($answer),
                'sort_order' => $i,
                'status' => true,
            ]);
        }
    }
}
