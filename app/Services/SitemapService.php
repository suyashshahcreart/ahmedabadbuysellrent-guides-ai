<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Insight;
use App\Models\Locality;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Tag;

class SitemapService
{
    public function enabled(): bool
    {
        return Setting::get('sitemap_enabled', '1') === '1';
    }

    public function include(string $type): bool
    {
        return Setting::get('sitemap_include_'.$type, '1') === '1';
    }

    public function types(): array
    {
        return ['articles', 'pages', 'categories', 'tags', 'localities', 'insights'];
    }

    public function index(): array
    {
        if (! $this->enabled()) {
            return [];
        }
        $base = rtrim(config('app.url'), '/');
        $files = [];
        foreach ($this->types() as $type) {
            if ($this->include($type)) {
                $files[] = $base.'/sitemap-'.$type.'.xml';
            }
        }

        return $files;
    }

    public function urls(string $type): array
    {
        if (! $this->enabled() || ! $this->include($type)) {
            return [];
        }
        $base = rtrim(config('app.url'), '/');

        $dynamic = match ($type) {
            'articles' => Article::published()->with('seo')->get()
                ->filter(fn ($m) => $m->isIndexable())
                ->map(fn ($m) => ['loc' => $m->publicUrl(), 'lastmod' => optional($m->updated_at)->toAtomString()])
                ->all(),
            'pages' => collect($this->staticPages())
                ->merge([['loc' => $base.'/', 'lastmod' => now()->toAtomString()]])
                ->merge(Page::published()->with('seo')->get()->filter(fn ($m) => $m->isIndexable())
                    ->map(fn ($m) => ['loc' => $m->publicUrl(), 'lastmod' => optional($m->updated_at)->toAtomString()]))
                ->unique('loc')
                ->values()
                ->all(),
            'categories' => Category::query()->where('status', 'published')->with('seo')->get()
                ->filter(fn ($m) => $m->isIndexable())
                ->map(fn ($m) => ['loc' => $m->publicUrl(), 'lastmod' => optional($m->updated_at)->toAtomString()])
                ->all(),
            'tags' => Tag::published()->with('seo')->get()
                ->filter(fn ($m) => $m->isIndexable())
                ->map(fn ($m) => ['loc' => $m->publicUrl(), 'lastmod' => optional($m->updated_at)->toAtomString()])
                ->all(),
            'localities' => Locality::published()->with('seo')->get()
                ->filter(fn ($m) => $m->isIndexable())
                ->map(fn ($m) => ['loc' => $m->publicUrl(), 'lastmod' => optional($m->updated_at)->toAtomString()])
                ->all(),
            'insights' => Insight::published()->with('seo')->get()
                ->map(fn ($m) => ['loc' => $m->publicUrl(), 'lastmod' => optional($m->updated_at)->toAtomString()])
                ->all(),
            default => [],
        };

        return $dynamic;
    }

    public function staticPages(): array
    {
        $raw = Setting::get('sitemap_static_pages', '[]');
        $rows = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->filter(fn ($r) => ! empty($r['loc']))
            ->map(fn ($r) => [
                'loc' => $r['loc'],
                'lastmod' => now()->toAtomString(),
                'changefreq' => $r['changefreq'] ?? 'weekly',
                'priority' => $r['priority'] ?? '0.5',
            ])
            ->all();
    }

    public function robots(): string
    {
        $base = rtrim(config('app.url'), '/');

        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /administrator',
            'Disallow: /preview',
            '',
            'Sitemap: '.$base.'/sitemap.xml',
            '',
        ]);
    }
}
