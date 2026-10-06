<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SavesSeo;
use App\Http\Controllers\Controller;
use App\Models\Insight;
use Illuminate\Http\Request;

class InsightController extends Controller
{
    use SavesSeo;

    public function __construct()
    {
        $this->middleware('permission:insights.view')->only(['index']);
        $this->middleware('permission:insights.create')->only(['create', 'store']);
        $this->middleware('permission:insights.edit')->only(['edit', 'update']);
        $this->middleware('permission:insights.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('admin.taxonomy.index', [
            'title' => 'Insights',
            'cardTitle' => 'Market Insights',
            'cardSubtitle' => 'Stat cards and insight pages',
            'createUrl' => route('administrator.insights.create'),
            'createLabel' => 'New Insight',
            'showSort' => true,
            'rows' => Insight::orderBy('sort_order')->orderBy('title')->get()->map(fn ($t) => [
                'id' => $t->id,
                'label' => $t->title,
                'slug' => $t->slug,
                'sort' => $t->sort_order,
                'active' => $t->status === 'published',
                'status_label' => $t->status === 'published' ? 'Published' : 'Draft',
                'edit' => route('administrator.insights.edit', $t),
                'delete' => route('administrator.insights.destroy', $t),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.insights.form', ['insight' => new Insight(['status' => 'draft'])]);
    }

    public function store(Request $request)
    {
        $i = new Insight();
        $this->persist($i, $request);

        return redirect('/administrator/insights');
    }

    public function edit(Insight $insight)
    {
        $insight->load('seo');

        return view('admin.insights.form', compact('insight'));
    }

    public function update(Request $request, Insight $insight)
    {
        $this->persist($insight, $request);

        return back()->with('status', 'Saved.');
    }

    public function destroy(Insight $insight)
    {
        $insight->delete();

        return redirect('/administrator/insights');
    }

    private function persist(Insight $i, Request $request): void
    {
        $data = $request->validate([
            'title' => 'required',
            'slug' => 'nullable',
            'stat' => 'nullable',
            'label' => 'nullable',
            'body' => 'nullable',
            'content' => 'nullable',
            'status' => 'required',
            'sort_order' => 'nullable|integer',
        ]);
        $data['slug'] = $data['slug'] ?: $this->slugify($data['title']);
        if ($data['status'] === 'published' && ! $i->published_at) {
            $data['published_at'] = now();
        }
        $i->fill($data)->save();
        $this->saveSeo($i, $request);
    }
}
