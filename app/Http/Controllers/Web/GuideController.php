<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Services\SeoResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(Request $request, SeoResolver $seo): View
    {
        $articles = Article::published()->with(['category', 'featuredMedia'])->latest('published_at')->get();
        $categories = Category::query()->where('status', 'published')->orderBy('sort_order')->get();
        $meta = $seo->siteDefaults();
        $meta['title'] = 'All property guides | Ahmedabad Guides';
        $meta['canonical'] = url('/guides/');

        return view('public.guides-index', compact('articles', 'categories', 'meta'));
    }

    public function show(string $slug, SeoResolver $seo): View
    {
        $article = Article::published()->with([
            'category', 'author', 'featuredMedia', 'seo', 'faqs',
            'tags' => fn ($q) => $q->published()->orderBy('sort_order')->orderBy('name'),
        ])->where('type', 'guide')->where('slug', $slug)->firstOrFail();

        return $this->articleView($article, $seo);
    }

    public function compare(string $slug, SeoResolver $seo): View
    {
        $article = Article::published()->with([
            'category', 'author', 'featuredMedia', 'seo', 'faqs',
            'tags' => fn ($q) => $q->published()->orderBy('sort_order')->orderBy('name'),
        ])->where('type', 'compare')->where('slug', $slug)->firstOrFail();

        return $this->articleView($article, $seo);
    }

    private function articleView(Article $article, SeoResolver $seo): View
    {
        $related = Article::published()->with(['category', 'featuredMedia'])
            ->where('id', '!=', $article->id)
            ->when($article->category_id, fn ($q) => $q->orderByRaw('category_id = ? desc', [$article->category_id]))
            ->latest('published_at')->take(3)->get();

        $prev = Article::published()->where('published_at', '<', $article->published_at)->latest('published_at')->first();
        $next = Article::published()->where('published_at', '>', $article->published_at)->oldest('published_at')->first();

        $meta = $seo->for($article, $article->title, $article->excerpt, optional($article->featuredMedia)->url(), $article->publicUrl());

        $usedIds = [];
        $article->content = preg_replace_callback('/<h([23])(\b[^>]*)>(.*?)<\/h\1>/si', function ($m) use (&$usedIds) {
            $level = $m[1];
            $attrs = $m[2];
            $text = trim(strip_tags($m[3]));
            if (preg_match('/\bid\s*=\s*([\'"])(.*?)\1/i', $attrs, $idMatch)) {
                $id = $idMatch[2];
            } else {
                $base = \Illuminate\Support\Str::slug(strip_tags($text)) ?: 'section';
                $id = $base;
                $n = 2;
                while (isset($usedIds[$id])) {
                    $id = $base.'-'.$n++;
                }
                $attrs .= ' id="'.$id.'"';
            }
            $usedIds[$id] = true;

            return '<h'.$level.$attrs.'>'.$m[3].'</h'.$level.'>';
        }, (string) $article->content) ?? (string) $article->content;

        preg_match_all('/<h2[^>]*\bid="([^"]+)"[^>]*>(.*?)<\/h2>/si', (string) $article->content, $m, PREG_SET_ORDER);
        $headings = collect($m)->map(fn ($h) => ['id' => $h[1], 'text' => trim(strip_tags($h[2]))]);

        $article->loadMissing('seoSchemas');
        $storedSchemas = $article->seoSchemas->where('is_active', true)->sortBy('sort_order');
        $jsonLdBlocks = $storedSchemas->map(fn ($s) => $s->decoded())->filter()->values()->all();

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $article->excerpt,
            'image' => optional($article->featuredMedia)->url(),
            'datePublished' => optional($article->published_at)?->toAtomString(),
            'dateModified' => optional($article->updated_at)?->toAtomString(),
            'author' => ['@type' => 'Person', 'name' => optional($article->author)->name ?? 'Ahmedabad Guides'],
        ];

        $crumbs = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => optional($article->category)->name, 'item' => optional($article->category)?->publicUrl()],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $article->title, 'item' => $article->publicUrl()],
        ];

        $faqLd = $article->publishedFaqs->isNotEmpty() ? [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $article->publishedFaqs->map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f->question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f->answer],
            ])->all(),
        ] : null;

        if ($jsonLdBlocks) {
            $jsonLd = null;
            $faqLd = null;
        }

        return view('public.article', compact('article', 'related', 'prev', 'next', 'meta', 'headings', 'jsonLd', 'jsonLdBlocks', 'crumbs', 'faqLd'));
    }
}
