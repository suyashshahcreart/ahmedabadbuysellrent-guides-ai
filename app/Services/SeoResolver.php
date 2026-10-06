<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Insight;
use App\Models\Locality;
use App\Models\Page;
use App\Models\Setting;
use App\Models\SeoTemplate;
use App\Models\Tag;
use Illuminate\Support\Str;

class SeoResolver
{
    public function for(object $model, string $fallbackTitle = '', ?string $fallbackDescription = null, ?string $fallbackImage = null, ?string $url = null): array
    {
        $seo = method_exists($model, 'seo') ? $model->seo : null;
        $tokens = $this->tokens($model, $fallbackTitle, $fallbackDescription);
        $template = $this->matchingTemplate($model);

        $rawTitle = $seo->seo_title
            ?? $this->fill($template?->title_template, $tokens)
            ?? ($model->title ?? $model->name ?? $fallbackTitle)
            ?? Setting::get('site_name', config('app.name'));

        $site = Setting::get('site_name', config('app.name'));
        $pageTitle = $rawTitle === $site ? $site : $rawTitle.' | Ahmedabad Guides';

        $description = $seo->meta_description
            ?? $this->fill($template?->description_template, $tokens)
            ?? $fallbackDescription
            ?? ($model->excerpt ?? $model->summary ?? $model->description ?? Setting::get('default_meta_description'));

        $image = optional($seo)->og_image
            ?? optional(optional($seo)->ogImageMedia)->url()
            ?? $fallbackImage
            ?? optional($model->featuredMedia ?? null)->url()
            ?? Setting::get('default_og_image');

        $canonical = $seo->canonical_url ?? $url ?? (method_exists($model, 'publicUrl') ? $model->publicUrl() : url('/'));
        $robots = $seo->robots ?? $template?->default_robots ?? 'index, follow';

        return [
            'title' => $pageTitle,
            'seo_title' => $rawTitle,
            'description' => Str::limit(strip_tags((string) $description), 160, ''),
            'canonical' => $canonical,
            'robots' => $robots,
            'og_title' => $seo->og_title ?? $rawTitle,
            'og_description' => $seo->og_description ?? Str::limit(strip_tags((string) $description), 160, ''),
            'og_image' => $image,
            'twitter_title' => $seo->twitter_title ?? $seo->og_title ?? $rawTitle,
            'twitter_description' => $seo->twitter_description ?? $seo->og_description ?? Str::limit(strip_tags((string) $description), 160, ''),
            'twitter_image' => $seo->twitter_image ?? $image,
        ];
    }

    public function siteDefaults(): array
    {
        $title = Setting::get('default_seo_title', Setting::get('site_name', config('app.name')));
        $description = Setting::get('default_meta_description', '');
        $image = Setting::get('default_og_image');
        $home = SeoTemplate::query()->where('is_active', true)->where('page_type', 'home')->first();
        $tokens = [
            '{title}' => $title,
            '{name}' => $title,
            '{excerpt}' => $description,
            '{site}' => Setting::get('site_name', config('app.name')),
            '{year}' => (string) now()->year,
        ];
        if ($home) {
            $title = $this->fill($home->title_template, $tokens) ?? $title;
            $description = $this->fill($home->description_template, $tokens) ?? $description;
        }

        return [
            'title' => $title,
            'seo_title' => $title,
            'description' => $description,
            'canonical' => rtrim(config('app.url'), '/').'/',
            'robots' => $home?->default_robots ?? 'index, follow',
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $image,
            'twitter_title' => $title,
            'twitter_description' => $description,
            'twitter_image' => $image,
        ];
    }

    private function matchingTemplate(object $model): ?SeoTemplate
    {
        $pageType = match (true) {
            $model instanceof Article && ($model->type ?? '') === 'compare' => 'compare',
            $model instanceof Article => 'article',
            $model instanceof Page => 'page',
            $model instanceof Category => 'category',
            $model instanceof Tag => 'tag',
            $model instanceof Locality => 'locality',
            $model instanceof Insight => 'insight',
            default => null,
        };
        if (! $pageType) {
            return null;
        }

        return SeoTemplate::query()
            ->where('is_active', true)
            ->where('page_type', $pageType)
            ->orderByRaw('model_type is null')
            ->first();
    }

    private function tokens(object $model, string $fallbackTitle, ?string $fallbackDescription): array
    {
        $title = $model->title ?? $model->name ?? $fallbackTitle;
        $excerpt = $model->excerpt ?? $model->summary ?? $model->description ?? $fallbackDescription ?? '';

        return [
            '{title}' => (string) $title,
            '{name}' => (string) ($model->name ?? $title),
            '{excerpt}' => Str::limit(strip_tags((string) $excerpt), 160, ''),
            '{site}' => (string) Setting::get('site_name', config('app.name')),
            '{year}' => (string) now()->year,
        ];
    }

    private function fill(?string $template, array $tokens): ?string
    {
        if ($template === null || trim($template) === '') {
            return null;
        }

        return strtr($template, $tokens);
    }
}
