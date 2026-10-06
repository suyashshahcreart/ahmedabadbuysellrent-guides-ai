<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Services\SeoResolver;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function show(string $slug, SeoResolver $seo): View
    {
        $author = Author::query()->where('slug', $slug)->where('status', true)->with('seo')->firstOrFail();
        $articles = $author->articles()->published()->with(['category', 'featuredMedia'])->latest('published_at')->get();
        $meta = $seo->for($author, $author->name, $author->bio, null, $author->publicUrl());

        return view('public.author', compact('author', 'articles', 'meta'));
    }
}
