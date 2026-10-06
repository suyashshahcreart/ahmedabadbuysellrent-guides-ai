<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\SeoResolver;
use Illuminate\View\View;

class TagController extends Controller
{
    public function show(string $slug, SeoResolver $seo): View
    {
        $tag = Tag::published()->where('slug', $slug)->with('seo')->firstOrFail();
        $articles = $tag->articles()->published()->with(['category', 'featuredMedia'])->latest('published_at')->get();
        $meta = $seo->for($tag, $tag->name, $tag->description, null, $tag->publicUrl());

        return view('public.tag', compact('tag', 'articles', 'meta'));
    }
}
