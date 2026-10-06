<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\SeoResolver;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug, SeoResolver $seo): View
    {
        $page = Page::published()->with(['seo', 'faqs', 'featuredMedia'])->where('slug', $slug)->firstOrFail();
        $meta = $seo->for($page, $page->title, $page->excerpt, optional($page->featuredMedia)->url(), $page->publicUrl());

        return view('public.page', compact('page', 'meta'));
    }
}
