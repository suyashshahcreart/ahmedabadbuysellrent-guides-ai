<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Locality;
use App\Services\SeoResolver;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(string $slug, SeoResolver $seo): View
    {
        $category = Category::query()->where('status', 'published')->where('slug', $slug)->with(['seo', 'faqs'])->firstOrFail();
        $articles = Article::published()->with(['category', 'featuredMedia'])->where('category_id', $category->id)->latest('published_at')->get();
        $localities = $slug === 'areas' ? Locality::published()->with('featuredMedia')->orderBy('sort_order')->get() : collect();
        $meta = $seo->for($category, $category->name, $category->description, null, $category->publicUrl());

        $itemList = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $articles->values()->map(fn ($a, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'url' => $a->publicUrl(),
                'name' => $a->title,
            ])->all(),
        ];

        return view('public.category', compact('category', 'articles', 'localities', 'meta', 'itemList'));
    }
}
