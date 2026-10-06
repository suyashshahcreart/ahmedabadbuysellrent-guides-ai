<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreviewController extends Controller
{
    public function article(Request $request, Article $article): View
    {
        abort_unless($request->hasValidSignature() || $request->user(), 403);
        $article->load(['category', 'author', 'featuredMedia', 'tags', 'seo', 'faqs']);
        $meta = [
            'title' => $article->title,
            'description' => $article->excerpt,
            'canonical' => $article->publicUrl(),
            'robots' => 'noindex, nofollow',
            'og_title' => $article->title,
            'og_description' => $article->excerpt,
            'og_image' => optional($article->featuredMedia)->url(),
            'twitter_title' => $article->title,
            'twitter_description' => $article->excerpt,
            'twitter_image' => optional($article->featuredMedia)->url(),
        ];
        $related = collect();
        $prev = $next = null;
        $headings = collect();
        $jsonLd = null;
        $crumbs = [];
        $faqLd = null;

        return view('public.article', compact('article', 'related', 'prev', 'next', 'meta', 'headings', 'jsonLd', 'crumbs', 'faqLd'));
    }

    public function page(Request $request, Page $page): View
    {
        abort_unless($request->hasValidSignature() || $request->user(), 403);
        $meta = [
            'title' => $page->title,
            'description' => $page->excerpt,
            'canonical' => $page->publicUrl(),
            'robots' => 'noindex, nofollow',
            'og_title' => $page->title,
            'og_description' => $page->excerpt,
            'og_image' => null,
            'twitter_title' => $page->title,
            'twitter_description' => $page->excerpt,
            'twitter_image' => null,
        ];

        return view('public.page', compact('page', 'meta'));
    }
}
