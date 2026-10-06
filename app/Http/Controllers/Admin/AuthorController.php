<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SavesSeo;
use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    use SavesSeo;

    public function __construct()
    {
        $this->middleware('permission:authors.view')->only(['index']);
        $this->middleware('permission:authors.create')->only(['create', 'store']);
        $this->middleware('permission:authors.edit')->only(['edit', 'update']);
        $this->middleware('permission:authors.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('admin.taxonomy.index', [
            'title' => 'Authors',
            'cardTitle' => 'Guide Authors',
            'cardSubtitle' => 'Authors shown on guide bylines and author pages',
            'createUrl' => route('administrator.authors.create'),
            'createLabel' => 'New Author',
            'showCount' => true,
            'countLabel' => 'Guides',
            'rows' => Author::withCount('articles')->orderBy('name')->get()->map(fn ($a) => [
                'id' => $a->id,
                'label' => $a->name,
                'slug' => $a->slug,
                'count' => $a->articles_count,
                'active' => (bool) $a->status,
                'edit' => route('administrator.authors.edit', $a),
                'delete' => route('administrator.authors.destroy', $a),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.authors.form', ['author' => new Author(['status' => true])]);
    }

    public function store(Request $request)
    {
        $a = new Author();
        $this->persist($a, $request);

        return redirect()->route('administrator.authors.index')->with('status', 'Author created.');
    }

    public function edit(Author $author)
    {
        $author->load('seo');

        return view('admin.authors.form', compact('author'));
    }

    public function update(Request $request, Author $author)
    {
        $this->persist($author, $request);

        return redirect()->route('administrator.authors.index')->with('status', 'Author saved.');
    }

    public function destroy(Author $author)
    {
        $author->delete();

        return redirect()->route('administrator.authors.index')->with('status', 'Author deleted.');
    }

    private function persist(Author $author, Request $request): void
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
        ]);
        $data['slug'] = $data['slug'] ?: $this->slugify($data['name']);
        $data['status'] = $request->boolean('status', true);
        $author->fill($data)->save();
        $this->saveSeo($author, $request);
    }
}
