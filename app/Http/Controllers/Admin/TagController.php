<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SavesSeo;
use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    use SavesSeo;

    public function __construct()
    {
        $this->middleware('permission:tags.view|tags.manage')->only(['index']);
        $this->middleware('permission:tags.create|tags.manage')->only(['create', 'store']);
        $this->middleware('permission:tags.edit|tags.manage')->only(['edit', 'update']);
        $this->middleware('permission:tags.delete|tags.manage')->only(['destroy']);
    }

    public function index()
    {
        $tags = Tag::withCount('articles')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.tags.index', compact('tags'));
    }

    public function create()
    {
        return view('admin.tags.form', ['tag' => new Tag(['status' => 'published', 'sort_order' => 0])]);
    }

    public function store(Request $request)
    {
        $t = new Tag();
        $this->persist($t, $request);

        return redirect()->route('administrator.tags.index')->with('status', 'Tag created.');
    }

    public function edit(Tag $tag)
    {
        $tag->load('seo');

        return view('admin.tags.form', compact('tag'));
    }

    public function update(Request $request, Tag $tag)
    {
        $this->persist($tag, $request);

        return redirect()->route('administrator.tags.index')->with('status', 'Tag saved.');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect()->route('administrator.tags.index')->with('status', 'Tag deleted.');
    }

    private function persist(Tag $tag, Request $request): void
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:published,draft',
        ]);
        $data['slug'] = $data['slug'] ?: $this->slugify($data['name']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $tag->fill($data)->save();
        $this->saveSeo($tag, $request);
    }
}
