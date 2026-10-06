<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SavesSeo;
use App\Http\Controllers\Controller;
use App\Models\SeoMeta;
use Illuminate\Http\Request;

class SeoMetaController extends Controller
{
    use SavesSeo;

    public function __construct()
    {
        $this->middleware('permission:seo.view|seo.manage')->only(['index']);
        $this->middleware('permission:seo.edit|seo.manage')->only(['edit', 'update']);
    }

    public function index()
    {
        $rows = SeoMeta::query()->with('seoable')->latest()->get();

        return view('admin.seo.meta.index', compact('rows'));
    }

    public function edit(SeoMeta $seoMeta)
    {
        $meta = $seoMeta->load('seoable');

        return view('admin.seo.meta.edit', ['meta' => $meta, 'seoable' => $meta->seoable]);
    }

    public function update(Request $request, SeoMeta $seoMeta)
    {
        $seoable = $seoMeta->seoable;
        if ($seoable) {
            $this->saveSeo($seoable, $request);
        } else {
            $payload = $request->only([
                'seo_title', 'meta_description', 'canonical_url', 'robots',
                'seo_keywords', 'og_title', 'og_description', 'og_image',
                'og_image_media_id', 'twitter_title', 'twitter_description', 'twitter_image',
            ]);
            if ($request->filled('seo_description') && empty($payload['meta_description'])) {
                $payload['meta_description'] = $request->input('seo_description');
            }
            $seoMeta->update($payload);
        }

        return redirect()->route('administrator.seo.meta.index')->with('status', 'SEO meta saved.');
    }
}
