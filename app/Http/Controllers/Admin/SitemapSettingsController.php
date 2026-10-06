<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SitemapSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:seo.sitemap.view|seo.view')->only(['index']);
        $this->middleware('permission:seo.sitemap.edit|seo.manage')->only(['update', 'rebuild', 'clear']);
    }

    public function index(SitemapService $sitemap)
    {
        $includes = [];
        foreach ($sitemap->types() as $type) {
            $includes[$type] = Setting::get('sitemap_include_'.$type, '1') === '1';
        }
        $static = Setting::get('sitemap_static_pages', '[]');
        $staticPages = is_array($static) ? $static : (json_decode((string) $static, true) ?: []);

        return view('admin.seo.sitemap.index', [
            'enabled' => Setting::get('sitemap_enabled', '1') === '1',
            'lastRebuild' => Setting::get('sitemap_last_rebuild'),
            'files' => $sitemap->index(),
            'includes' => $includes,
            'staticPages' => $staticPages,
            'cacheDriver' => config('cache.default'),
            'sitemapIndex' => rtrim(config('app.url'), '/').'/sitemap.xml',
        ]);
    }

    public function update(Request $request, SitemapService $sitemap)
    {
        Setting::put('sitemap_enabled', $request->boolean('enabled') ? '1' : '0');
        foreach ($sitemap->types() as $type) {
            Setting::put('sitemap_include_'.$type, $request->boolean('include_'.$type) ? '1' : '0');
        }
        $rows = [];
        foreach ($request->input('static', []) as $row) {
            if (! empty($row['loc'])) {
                $rows[] = [
                    'loc' => $row['loc'],
                    'changefreq' => $row['changefreq'] ?? 'weekly',
                    'priority' => $row['priority'] ?? '0.5',
                ];
            }
        }
        Setting::put('sitemap_static_pages', json_encode($rows));

        return back()->with('status', 'Sitemap settings saved.');
    }

    public function rebuild()
    {
        Setting::put('sitemap_last_rebuild', now()->toDateTimeString());
        Cache::forget('site_settings');

        return back()->with('status', 'Sitemap cache timestamp updated. Public /sitemap.xml is generated on request.');
    }

    public function clear()
    {
        Cache::forget('site_settings');
        Setting::put('sitemap_last_rebuild', now()->toDateTimeString());

        return back()->with('status', 'Sitemap cache cleared.');
    }
}
