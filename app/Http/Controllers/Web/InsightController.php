<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Insight;
use App\Services\SeoResolver;
use Illuminate\View\View;

class InsightController extends Controller
{
    public function show(string $slug, SeoResolver $seo): View
    {
        $insight = Insight::published()->with('seo')->where('slug', $slug)->firstOrFail();
        $meta = $seo->for($insight, $insight->title, $insight->body, null, $insight->publicUrl());

        return view('public.insight', compact('insight', 'meta'));
    }
}
