<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Locality;
use App\Services\SeoResolver;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function show(string $slug, SeoResolver $seo): View
    {
        $locality = Locality::published()->with(['featuredMedia', 'seo', 'faqs'])->where('slug', $slug)->firstOrFail();
        $meta = $seo->for($locality, $locality->name, $locality->summary, optional($locality->featuredMedia)->url(), $locality->publicUrl());

        return view('public.area', compact('locality', 'meta'));
    }
}
