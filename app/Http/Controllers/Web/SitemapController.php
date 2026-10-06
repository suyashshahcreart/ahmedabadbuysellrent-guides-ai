<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SitemapService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SitemapController extends Controller
{
    public function index(SitemapService $sitemaps): Response
    {
        return response()->view('xml.sitemap-index', ['sitemaps' => $sitemaps->index()])->header('Content-Type', 'application/xml');
    }

    public function show(string $type, SitemapService $sitemaps): Response
    {
        abort_unless(in_array($type, ['articles', 'pages', 'categories', 'tags', 'localities', 'insights'], true), 404);

        return response()->view('xml.sitemap-urlset', ['urls' => $sitemaps->urls($type)])->header('Content-Type', 'application/xml');
    }

    public function robots(SitemapService $sitemaps): SymfonyResponse
    {
        return response($sitemaps->robots(), 200, ['Content-Type' => 'text/plain']);
    }
}
