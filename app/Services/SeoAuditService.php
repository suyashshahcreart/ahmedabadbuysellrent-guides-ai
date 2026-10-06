<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Insight;
use App\Models\Page;
use App\Models\Setting;

class SeoAuditService
{
    public function run(): array
    {
        $issues = [];
        $this->auditArticles($issues);
        $this->auditPages($issues);

        $errorCount = collect($issues)->where('level', 'error')->count();
        $warningCount = collect($issues)->where('level', 'warning')->count();
        $score = max(0, 100 - ($errorCount * 8) - ($warningCount * 3));

        return compact('issues', 'errorCount', 'warningCount', 'score');
    }

    private function auditArticles(array &$issues): void
    {
        foreach (Article::with(['seo', 'featuredMedia', 'category'])->get() as $article) {
            $label = $article->title;
            $seo = $article->seo;
            $title = $seo->seo_title ?? $article->title;
            if (! $title) {
                $issues[] = $this->issue($article, 'error', 'Missing SEO title', $label);
            } elseif (strlen($title) > 60) {
                $issues[] = $this->issue($article, 'warning', 'SEO title too long', $label);
            }
            $desc = $seo->meta_description ?? $article->excerpt;
            if (! $desc) {
                $issues[] = $this->issue($article, 'error', 'Missing meta description', $label);
            } elseif (strlen(strip_tags((string) $desc)) > 160) {
                $issues[] = $this->issue($article, 'warning', 'Meta description too long', $label);
            }
            if (! ($seo->canonical_url ?? $article->canonical_url ?? $article->path)) {
                $issues[] = $this->issue($article, 'warning', 'Missing canonical', $label);
            }
            if (! $article->featured_media_id) {
                $issues[] = $this->issue($article, 'warning', 'Missing featured image', $label);
            } elseif ($article->featuredMedia && ! $article->featuredMedia->alt) {
                $issues[] = $this->issue($article, 'warning', 'Missing alt text', $label);
            }
            if (! $article->category_id) {
                $issues[] = $this->issue($article, 'error', 'Missing category', $label);
            }
            $text = strip_tags((string) $article->content);
            if (str_word_count($text) < 300) {
                $issues[] = $this->issue($article, 'warning', 'Thin content', $label);
            }
            if (! preg_match('/<h1/i', (string) $article->content) && ! $article->title) {
                $issues[] = $this->issue($article, 'error', 'Missing H1', $label);
            }
            if (! preg_match('/href=["\']\//i', (string) $article->content)) {
                $issues[] = $this->issue($article, 'warning', 'No internal links', $label);
            }
        }

        $dupSlugs = Article::select('slug')->groupBy('slug')->havingRaw('count(*) > 1')->pluck('slug');
        foreach ($dupSlugs as $slug) {
            $issues[] = ['level' => 'error', 'rule' => 'Duplicate slug', 'label' => $slug, 'url' => null];
        }
    }

    private function auditPages(array &$issues): void
    {
        foreach (Page::with('seo')->get() as $page) {
            $desc = optional($page->seo)->meta_description ?? $page->excerpt;
            if (! $desc) {
                $issues[] = $this->issue($page, 'warning', 'Missing meta description', $page->title);
            }
        }
    }

    private function issue($model, string $level, string $rule, string $label): array
    {
        return [
            'level' => $level,
            'rule' => $rule,
            'label' => $label,
            'url' => method_exists($model, 'publicUrl') ? $model->publicUrl() : null,
        ];
    }
}
