<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use App\Services\SeoResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request, SearchService $search, SeoResolver $seo): View
    {
        $q = (string) $request->query('q', '');
        $type = $request->query('type');
        $results = $q !== '' ? $search->search($q, $type) : collect();
        $meta = $seo->siteDefaults();
        $meta['title'] = 'Search | Ahmedabad Guides';
        $meta['robots'] = 'noindex, follow';
        $meta['canonical'] = url('/search/');

        return view('public.search', compact('q', 'type', 'results', 'meta'));
    }
}
