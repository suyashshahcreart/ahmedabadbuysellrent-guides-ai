<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SavesSeo;
use App\Http\Controllers\Controller;
use App\Models\Locality;
use App\Services\HtmlSanitizer;
use Illuminate\Http\Request;

class LocalityController extends Controller
{
    use SavesSeo;

    public function __construct()
    {
        $this->middleware('permission:localities.view')->only(['index']);
        $this->middleware('permission:localities.create')->only(['create', 'store']);
        $this->middleware('permission:localities.edit')->only(['edit', 'update']);
        $this->middleware('permission:localities.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('admin.taxonomy.index', [
            'title' => 'Localities',
            'cardTitle' => 'Locality Guides',
            'cardSubtitle' => 'Area guides shown on the public site',
            'createUrl' => route('administrator.localities.create'),
            'createLabel' => 'New Locality',
            'showSort' => true,
            'rows' => Locality::orderBy('sort_order')->orderBy('name')->get()->map(fn ($t) => [
                'id' => $t->id,
                'label' => $t->name,
                'slug' => $t->slug,
                'sort' => $t->sort_order,
                'active' => $t->status === 'published',
                'status_label' => $t->status === 'published' ? 'Published' : 'Draft',
                'edit' => route('administrator.localities.edit', $t),
                'delete' => route('administrator.localities.destroy', $t),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.localities.form', ['locality' => new Locality(['status' => 'draft'])]);
    }

    public function store(Request $request, HtmlSanitizer $html)
    {
        $l = new Locality();
        $this->persist($l, $request, $html);

        return redirect('/administrator/localities/'.$l->id.'/edit');
    }

    public function edit(Locality $locality)
    {
        $locality->load(['seo', 'faqs', 'featuredMedia']);

        return view('admin.localities.form', compact('locality'));
    }

    public function update(Request $request, Locality $locality, HtmlSanitizer $html)
    {
        $locality->recordRevision($request->user()->id);
        $this->persist($locality, $request, $html);

        return back()->with('status', 'Saved.');
    }

    public function destroy(Locality $locality)
    {
        $locality->delete();

        return redirect('/administrator/localities');
    }

    private function persist(Locality $l, Request $request, HtmlSanitizer $html): void
    {
        $data = $request->validate([
            'name' => 'required',
            'slug' => 'nullable',
            'short' => 'nullable',
            'summary' => 'nullable',
            'overview' => 'nullable',
            'property_market' => 'nullable',
            'average_price' => 'nullable',
            'rental_information' => 'nullable',
            'connectivity' => 'nullable',
            'schools' => 'nullable',
            'hospitals' => 'nullable',
            'shopping' => 'nullable',
            'lifestyle' => 'nullable',
            'investment_potential' => 'nullable',
            'status' => 'required',
            'sort_order' => 'nullable|integer',
            'featured_media_id' => 'nullable|exists:media_assets,id',
            'tags' => 'nullable|string',
            'pros' => 'nullable|string',
            'cons' => 'nullable|string',
        ]);
        $data['slug'] = $data['slug'] ?: $this->slugify($data['name']);
        $data['tags'] = array_values(array_filter(array_map('trim', explode(',', (string) ($data['tags'] ?? '')))));
        $data['pros'] = array_values(array_filter(array_map('trim', explode("\n", (string) ($data['pros'] ?? '')))));
        $data['cons'] = array_values(array_filter(array_map('trim', explode("\n", (string) ($data['cons'] ?? '')))));
        foreach (['overview', 'property_market', 'rental_information', 'connectivity', 'schools', 'hospitals', 'shopping', 'lifestyle', 'investment_potential'] as $field) {
            $data[$field] = $html->clean($data[$field] ?? '');
        }
        if ($data['status'] === 'published' && ! $l->published_at) {
            $data['published_at'] = now();
        }
        $l->fill($data)->save();
        $this->saveSeo($l, $request);
        $this->saveFaqs($l, $request);
    }
}
