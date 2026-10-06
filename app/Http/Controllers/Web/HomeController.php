<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Insight;
use App\Models\Locality;
use App\Services\SeoResolver;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(SeoResolver $seo): View
    {
        $featured = Article::published()->with(['category', 'featuredMedia'])->where('featured', true)->latest('published_at')->take(3)->get();
        $trending = Article::published()->with(['category', 'featuredMedia'])->where('trending', true)->latest('published_at')->take(6)->get();
        $latest = Article::published()->with(['category', 'featuredMedia'])->latest('published_at')->take(5)->get();
        $categories = Category::query()->where('status', 'published')->orderBy('sort_order')->get();
        $localities = Locality::published()->with('featuredMedia')->orderBy('sort_order')->get();
        $insights = Insight::published()->orderBy('sort_order')->take(3)->get();
        $faqs = Faq::query()->whereNull('faqable_id')->where('status', true)->orderBy('sort_order')->get();
        $meta = $seo->siteDefaults();

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $meta['seo_title'],
            'url' => url('/'),
            'description' => $meta['description'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/search/?q={search_term_string}'),
                'query-input' => 'required name=search_term_string',
            ],
        ];

        return view('public.home', compact('featured', 'trending', 'latest', 'categories', 'localities', 'insights', 'faqs', 'meta', 'jsonLd'));
    }
}
