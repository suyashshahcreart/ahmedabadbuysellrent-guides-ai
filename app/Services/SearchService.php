<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Insight;
use App\Models\Locality;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Support\Collection;

class SearchService
{
    public function search(string $q, ?string $type = null, int $perPage = 12)
    {
        $q = trim($q);
        if ($q === '') {
            return collect();
        }

        $results = collect();

        if (! $type || $type === 'article') {
            $results = $results->merge(
                Article::published()
                    ->with(['category', 'featuredMedia'])
                    ->where(function ($query) use ($q) {
                        $query->whereRaw('MATCH(title, excerpt, content) AGAINST(? IN BOOLEAN MODE)', [$this->boolean($q)])
                            ->orWhereHas('tags', fn ($tags) => $tags->published()->where('name', 'like', '%'.$q.'%'));
                    })
                    ->get()
                    ->map(fn ($m) => $this->row(
                        $m,
                        'article',
                        $m->title,
                        $m->excerpt,
                        $m->publicUrl(),
                        optional($m->category)->displayLabel() ?? 'Guide'
                    ))
            );
        }
        if (! $type || $type === 'page') {
            $results = $results->merge(
                Page::published()
                    ->whereRaw('MATCH(title, excerpt, content) AGAINST(? IN BOOLEAN MODE)', [$this->boolean($q)])
                    ->get()
                    ->map(fn ($m) => $this->row($m, 'page', $m->title, $m->excerpt, $m->publicUrl(), 'Page'))
            );
        }
        if (! $type || $type === 'locality') {
            $results = $results->merge(
                Locality::published()
                    ->whereRaw('MATCH(name, summary, overview) AGAINST(? IN BOOLEAN MODE)', [$this->boolean($q)])
                    ->get()
                    ->map(fn ($m) => $this->row($m, 'locality', $m->name, $m->summary, $m->publicUrl(), 'Area'))
            );
        }
        if (! $type || $type === 'insight') {
            $results = $results->merge(
                Insight::published()
                    ->whereRaw('MATCH(title, body, content) AGAINST(? IN BOOLEAN MODE)', [$this->boolean($q)])
                    ->get()
                    ->map(fn ($m) => $this->row($m, 'insight', $m->title, $m->body, $m->publicUrl(), 'Market'))
            );
        }
        if (! $type || $type === 'category') {
            $results = $results->merge(
                Category::query()->where('status', 'published')
                    ->whereRaw('MATCH(name, description) AGAINST(? IN BOOLEAN MODE)', [$this->boolean($q)])
                    ->get()
                    ->map(fn ($m) => $this->row($m, 'category', $m->name, $m->description, $m->publicUrl(), $m->displayLabel()))
            );
        }
        if (! $type || $type === 'tag') {
            $results = $results->merge(
                Tag::published()
                    ->where(function ($query) use ($q) {
                        $query->where('name', 'like', '%'.$q.'%')
                            ->orWhere('description', 'like', '%'.$q.'%');
                    })
                    ->get()
                    ->map(fn ($m) => $this->row($m, 'tag', $m->name, $m->description, $m->publicUrl(), 'Tag'))
            );
        }

        return $results->values();
    }

    public function suggest(string $q): Collection
    {
        $q = trim($q);
        if (strlen($q) < 2) {
            return collect();
        }

        return Article::published()
            ->where('title', 'like', $q.'%')
            ->limit(8)
            ->get(['title', 'path']);
    }

    private function boolean(string $q): string
    {
        $terms = preg_split('/\s+/', $q) ?: [];

        return collect($terms)->filter()->map(fn ($t) => '+'.preg_replace('/[+\-><()~*"@]+/', '', $t).'*')->implode(' ');
    }

    private function row($model, string $type, string $title, ?string $excerpt, string $url, ?string $label = null): array
    {
        return compact('model', 'type', 'title', 'excerpt', 'url', 'label');
    }
}
