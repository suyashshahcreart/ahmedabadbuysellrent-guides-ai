<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SavesSeo;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PageController extends Controller
{
    use SavesSeo;

    public function __construct()
    {
        $this->middleware('permission:pages.view|pages.manage')->only(['index']);
        $this->middleware('permission:pages.create|pages.manage')->only(['create', 'store']);
        $this->middleware('permission:pages.edit|pages.manage')->only(['edit', 'update', 'preview']);
        $this->middleware('permission:pages.delete|pages.manage')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $activeView = $request->query('status', 'all') ?: 'all';
        $q = Page::query();
        if ($activeView !== 'all') {
            $q->where('status', $activeView);
        }

        return view('admin.pages.index', [
            'pages' => $q->latest()->get(),
            'activeView' => $activeView,
            'counts' => [
                'all' => Page::count(),
                'published' => Page::where('status', 'published')->count(),
                'draft' => Page::where('status', 'draft')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.pages.form', ['page' => new Page(['status' => 'draft'])]);
    }

    public function store(Request $request, HtmlSanitizer $html)
    {
        $page = new Page();
        $this->persist($page, $request, $html);

        return redirect('/administrator/pages/'.$page->id.'/edit')->with('status', 'Created.');
    }

    public function edit(Page $page)
    {
        $page->load(['seo', 'faqs', 'featuredMedia']);

        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, Page $page, HtmlSanitizer $html)
    {
        $page->recordRevision($request->user()->id);
        $this->persist($page, $request, $html);

        return back()->with('status', 'Saved.');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect('/administrator/pages')->with('status', 'Trashed.');
    }

    public function preview(Page $page)
    {
        return redirect(URL::temporarySignedRoute('preview.page', now()->addHours(6), ['page' => $page->id]));
    }

    private function persist(Page $page, Request $request, HtmlSanitizer $html): void
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt' => 'nullable',
            'content' => 'nullable',
            'status' => 'required',
            'published_at' => 'nullable|date',
            'scheduled_at' => 'nullable|date',
            'featured_media_id' => 'nullable|exists:media_assets,id',
        ]);
        $data['slug'] = $data['slug'] ?: $this->slugify($data['title']);
        $data['path'] = $data['slug'].'/';
        $data['content'] = $html->clean($data['content'] ?? '');
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        $page->fill($data)->save();
        $this->saveSeo($page, $request);
        $this->saveFaqs($page, $request);
    }
}
