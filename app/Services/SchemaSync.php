<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Page;
use App\Models\SeoSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SchemaSync
{
    public static function supportedTypes(): array
    {
        return [
            'Article',
            'BlogPosting',
            'WebPage',
            'BreadcrumbList',
            'FAQPage',
            'ImageObject',
            'Organization',
        ];
    }

    public function sync(Model $model): void
    {
        if ($model instanceof Article) {
            $this->upsert($model, 'Article', 'Article', $this->article($model), 0);
            $this->upsert($model, 'BreadcrumbList', 'BreadcrumbList', $this->breadcrumbs($model), 1);
            $faqs = $this->activeFaqs($model);
            if ($faqs->isNotEmpty()) {
                $this->upsert($model, 'FAQPage', 'FAQPage', $this->faqPage($faqs), 2);
            }

            return;
        }

        if ($model instanceof Page) {
            $this->upsert($model, 'WebPage', 'WebPage', $this->webPage($model), 0);
        }
    }

    public function generateOne(Article $article, string $type): SeoSchema
    {
        $payload = $this->buildPayload($article, $type);
        if ($payload === null) {
            throw new \InvalidArgumentException("Cannot generate {$type} schema for this guide (missing required content).");
        }

        return $this->upsert(
            $article,
            $type,
            $type,
            $payload['json'],
            $payload['sort']
        );
    }

    /**
     * @return array{generated: array<int, SeoSchema>, updated: array<int, SeoSchema>, skipped: array<int, string>, schemas: array<int, array>}
     */
    public function generateAll(Article $article, bool $replace = false): array
    {
        if ($replace) {
            $article->seoSchemas()->where('is_auto_generated', true)->delete();
        }

        $generated = [];
        $updated = [];
        $skipped = [];
        $types = ['Article', 'BlogPosting', 'WebPage', 'BreadcrumbList', 'FAQPage', 'ImageObject'];

        foreach ($types as $i => $type) {
            $payload = $this->buildPayload($article, $type);
            if ($payload === null) {
                $skipped[] = $type;
                continue;
            }
            $existing = $article->seoSchemas()->where('schema_type', $type)->first();
            $schema = $this->upsert($article, $type, $type, $payload['json'], $payload['sort'] ?? $i);
            if ($existing) {
                $updated[] = $schema;
            } else {
                $generated[] = $schema;
            }
        }

        $schemas = $article->seoSchemas()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (SeoSchema $s) => $this->formatSchema($s))
            ->values()
            ->all();

        return compact('generated', 'updated', 'skipped', 'schemas');
    }

    public function formatSchema(SeoSchema $schema): array
    {
        $json = $schema->schema_json;
        $decoded = json_decode((string) $json, true);
        if (is_array($decoded)) {
            $json = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return [
            'id' => $schema->id,
            'schema_type' => $schema->schema_type,
            'schema_name' => $schema->schema_name ?? '',
            'schema_json' => $json,
            'is_auto_generated' => (bool) $schema->is_auto_generated,
            'sort_order' => (int) $schema->sort_order,
            'is_active' => (bool) $schema->is_active,
        ];
    }

    public function article(Article $article): array
    {
        $article->loadMissing(['author', 'featuredMedia', 'seo', 'tags', 'category']);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => optional($article->seo)->meta_description ?: $article->excerpt,
            'image' => optional($article->featuredMedia)->url(),
            'datePublished' => optional($article->published_at)?->toAtomString(),
            'dateModified' => optional($article->updated_at)?->toAtomString(),
            'author' => ['@type' => 'Person', 'name' => optional($article->author)->name ?? 'Ahmedabad Guides'],
            'keywords' => $article->tags->pluck('name')->filter()->implode(', ') ?: null,
            'articleSection' => optional($article->category)->name,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $article->publicUrl(),
            ],
            'url' => $article->publicUrl(),
        ];
    }

    public function blogPosting(Article $article): array
    {
        $base = $this->article($article);
        $base['@type'] = 'BlogPosting';

        return $base;
    }

    public function webPage(Model $model): array
    {
        $title = $model->title ?? $model->name ?? 'Page';
        $url = method_exists($model, 'publicUrl') ? $model->publicUrl() : url('/');
        $description = null;
        if (method_exists($model, 'seo')) {
            $model->loadMissing('seo');
            $description = optional($model->seo)->meta_description;
        }
        $description = $description ?: ($model->excerpt ?? null);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $description,
            'url' => $url,
        ];
    }

    public function breadcrumbs(Article $article): array
    {
        $article->loadMissing('category');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => optional($article->category)->name, 'item' => optional($article->category)?->publicUrl()],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $article->title, 'item' => $article->publicUrl()],
            ],
        ];
    }

    public function faqPage($faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqs)->map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f->question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags((string) $f->answer)],
            ])->values()->all(),
        ];
    }

    public function imageObject(Article $article): ?array
    {
        $article->loadMissing('featuredMedia');
        $media = $article->featuredMedia;
        if (! $media) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ImageObject',
            'contentUrl' => $media->url(),
            'url' => $media->url(),
            'name' => $media->alt ?: $article->title,
            'width' => $media->width,
            'height' => $media->height,
        ];
    }

    /**
     * @return array{json: array, sort: int}|null
     */
    private function buildPayload(Article $article, string $type): ?array
    {
        return match ($type) {
            'Article' => ['json' => $this->article($article), 'sort' => 0],
            'BlogPosting' => ['json' => $this->blogPosting($article), 'sort' => 1],
            'WebPage' => ['json' => $this->webPage($article), 'sort' => 2],
            'BreadcrumbList' => ['json' => $this->breadcrumbs($article), 'sort' => 3],
            'FAQPage' => ($faqs = $this->activeFaqs($article))->isNotEmpty()
                ? ['json' => $this->faqPage($faqs), 'sort' => 4]
                : null,
            'ImageObject' => ($img = $this->imageObject($article))
                ? ['json' => $img, 'sort' => 5]
                : null,
            'Organization' => ['json' => [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => config('app.name'),
                'url' => url('/'),
            ], 'sort' => 6],
            default => throw new \InvalidArgumentException("Unsupported schema type: {$type}"),
        };
    }

    private function activeFaqs(Article $article): Collection
    {
        $article->loadMissing('faqs');

        return $article->faqs->where('status', true)->values();
    }

    private function upsert(Model $model, string $type, string $name, array $json, int $sort): SeoSchema
    {
        return SeoSchema::query()->updateOrCreate(
            [
                'schemaable_type' => $model->getMorphClass(),
                'schemaable_id' => $model->getKey(),
                'schema_type' => $type,
            ],
            [
                'schema_name' => $name,
                'schema_json' => json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'is_auto_generated' => true,
                'sort_order' => $sort,
                'is_active' => true,
            ]
        );
    }
}
