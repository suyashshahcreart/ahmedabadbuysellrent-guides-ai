<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesArticleSchemas;
use App\Http\Controllers\Admin\Concerns\SavesSeo;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Redirect;
use App\Models\Tag;
use App\Services\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ArticleController extends Controller
{
    use SavesSeo;
    use ManagesArticleSchemas;

    public function __construct()
    {
        $this->middleware('permission:articles.view')->only(['index']);
        $this->middleware('permission:articles.create')->only(['create', 'store', 'duplicate']);
        $this->middleware('permission:articles.edit')->only([
            'edit', 'update', 'preview', 'quickUpdate',
            'storeSeoSchema', 'updateSeoSchema', 'deleteSeoSchema',
            'generateSeoSchema', 'generateAllSeoSchemas',
        ]);
        $this->middleware('permission:articles.delete')->only(['destroy']);
        $this->middleware('permission:articles.edit|articles.publish')->only(['bulk', 'restore']);
        $this->middleware('permission:articles.view|articles.create|articles.edit')->only(['suggestTags']);
    }

    public function index(Request $request)
    {
        $activeView = $request->query('status', 'all') ?: 'all';
        $q = Article::query()->with(['category', 'author', 'tags']);
        if ($activeView === 'trash') {
            $q->onlyTrashed();
        } elseif ($activeView === 'featured') {
            $q->where('featured', true);
        } elseif ($activeView !== 'all') {
            $q->where('status', $activeView);
        }
        if ($s = $request->query('s')) {
            $q->where('title', 'like', '%'.$s.'%');
        }
        if ($cat = $request->query('category_id')) {
            $q->where('category_id', $cat);
        }
        if ($author = $request->query('author_id')) {
            $q->where('author_id', $author);
        }
        if ($tag = $request->query('tag_id')) {
            $q->whereHas('tags', fn ($qq) => $qq->where('tags.id', $tag));
        }
        if ($month = $request->query('month')) {
            $q->whereRaw("DATE_FORMAT(COALESCE(published_at, created_at), '%Y-%m') = ?", [$month]);
        }

        $months = Article::query()
            ->selectRaw("DATE_FORMAT(COALESCE(published_at, created_at), '%Y-%m') as ym")
            ->groupBy('ym')
            ->orderByDesc('ym')
            ->pluck('ym')
            ->filter();

        return view('admin.articles.index', [
            'articles' => $q->latest()->get(),
            'activeView' => $activeView,
            'months' => $months,
            'counts' => [
                'all' => Article::count(),
                'published' => Article::where('status', 'published')->count(),
                'draft' => Article::where('status', 'draft')->count(),
                'trash' => Article::onlyTrashed()->count(),
                'featured' => Article::where('featured', true)->count(),
            ],
            'filterOptions' => [
                'categories' => Category::orderBy('name')->get(),
                'tags' => Tag::orderBy('name')->get(),
                'authors' => Author::orderBy('name')->get(),
            ],
        ]);
    }

    public function bulk(Request $request)
    {
        $ids = $request->input('ids', []);
        $action = $request->input('bulk_action');
        if (! $ids) {
            return back()->with('warning', 'Select at least one item.');
        }
        if ($action === 'trash') {
            Article::whereIn('id', $ids)->delete();
        } elseif ($action === 'restore') {
            Article::onlyTrashed()->whereIn('id', $ids)->restore();
        } elseif ($action === 'delete') {
            Article::onlyTrashed()->whereIn('id', $ids)->forceDelete();
        }

        return back()->with('status', 'Bulk action applied.');
    }

    public function create()
    {
        return view('admin.articles.form', [
            'article' => new Article(['type' => 'guide', 'status' => 'draft']),
            'categories' => Category::orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'mostUsedTags' => $this->mostUsedTags(),
        ]);
    }

    public function suggestTags(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $tags = Tag::query()
            ->when($q !== '', fn ($qq) => $qq->where('name', 'like', '%'.$q.'%'))
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name']);

        return response()->json($tags);
    }

    public function store(Request $request, HtmlSanitizer $html)
    {
        $article = new Article();
        $this->persist($article, $request, $html);

        return redirect('/administrator/articles/'.$article->id.'/edit')->with('status', 'Article created.');
    }

    public function edit(Article $article)
    {
        $article->load(['seo', 'faqs', 'tags', 'featuredMedia', 'seoSchemas' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')]);

        return view('admin.articles.form', [
            'article' => $article,
            'categories' => Category::orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'mostUsedTags' => $this->mostUsedTags(),
        ]);
    }

    public function update(Request $request, Article $article, HtmlSanitizer $html)
    {
        $oldPath = $article->path;
        $wasPublished = $article->status === 'published';
        $article->recordRevision($request->user()->id);
        $this->persist($article, $request, $html);
        if ($wasPublished && $oldPath && $oldPath !== $article->path) {
            Redirect::updateOrCreate(
                ['from_path' => '/'.ltrim($oldPath, '/')],
                ['to_url' => $article->publicUrl(), 'status_code' => 301, 'active' => true, 'notes' => 'Slug change']
            );
        }

        return back()->with('status', 'Article saved.');
    }

    public function destroy($id)
    {
        $article = Article::withTrashed()->findOrFail($id);
        if ($article->trashed()) {
            $article->forceDelete();

            return redirect('/administrator/articles?status=trash')->with('status', 'Deleted permanently.');
        }
        $article->delete();

        return redirect('/administrator/articles')->with('status', 'Moved to trash.');
    }

    public function restore(int $id)
    {
        Article::withTrashed()->findOrFail($id)->restore();

        return back()->with('status', 'Restored.');
    }

    public function duplicate(Article $article)
    {
        $copy = $article->replicate();
        $copy->title = $article->title.' (copy)';
        $copy->slug = $article->slug.'-copy-'.time();
        $copy->path = ($article->type === 'compare' ? 'compare/' : 'guides/').$copy->slug.'/';
        $copy->status = 'draft';
        $copy->published_at = null;
        $copy->save();

        return redirect('/administrator/articles/'.$copy->id.'/edit');
    }

    public function quickUpdate(Request $request, Article $article)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'status' => 'required|in:draft,pending_review,scheduled,published,archived',
            'category_id' => 'nullable|exists:categories,id',
            'author_id' => 'nullable|exists:authors,id',
            'featured' => 'sometimes|boolean',
        ]);
        $data['slug'] = $data['slug'] ?: $this->slugify($data['title']);
        $prefix = $article->type === 'compare' ? 'compare/' : 'guides/';
        $data['path'] = $prefix.$data['slug'].'/';
        $data['featured'] = $request->boolean('featured');
        if ($data['status'] === 'published' && ! $article->published_at) {
            $data['published_at'] = now();
        }
        $article->fill($data)->save();

        return back()->with('status', 'Updated.');
    }

    public function preview(Article $article)
    {
        $url = URL::temporarySignedRoute('preview.article', now()->addHours(6), ['article' => $article->id]);

        return redirect($url);
    }

    private function persist(Article $article, Request $request, HtmlSanitizer $html): void
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'type' => 'required|in:guide,compare',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'author_id' => 'nullable|exists:authors,id',
            'featured_media_id' => 'nullable|exists:media_assets,id',
            'status' => 'required|in:draft,pending_review,scheduled,published,archived,trash',
            'published_at' => 'nullable|date',
            'scheduled_at' => 'nullable|date',
            'featured' => 'sometimes|boolean',
            'trending' => 'sometimes|boolean',
            'canonical_url' => 'nullable|url',
            'reading_time' => 'nullable|integer',
            'tags' => 'nullable|array',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'tag_names' => 'nullable|array',
            'tag_names.*' => 'string|max:100',
        ]);
        $data['slug'] = $data['slug'] ?: $this->slugify($data['title']);
        $prefix = $data['type'] === 'compare' ? 'compare/' : 'guides/';
        $data['path'] = $prefix.$data['slug'].'/';
        $data['content'] = $html->clean($data['content'] ?? '');
        if (empty($data['reading_time'])) {
            $data['reading_time'] = $html->readingMinutes($data['content']);
        }
        $data['featured'] = $request->boolean('featured');
        $data['trending'] = $request->boolean('trending');
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        unset($data['tags'], $data['tag_ids'], $data['tag_names']);
        $article->fill($data);
        if (! $article->created_by) {
            $article->created_by = $request->user()->id;
        }
        $article->save();
        $this->syncArticleTags($article, $request);
        $this->saveSeo($article, $request);
        $this->saveFaqs($article, $request);
    }

    private function syncArticleTags(Article $article, Request $request): void
    {
        $ids = collect($request->input('tag_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        // Backward-compat with old multi-select name="tags[]"
        foreach ((array) $request->input('tags', []) as $id) {
            if (is_numeric($id)) {
                $ids->push((int) $id);
            }
        }
        $ids = $ids->unique()->values();

        foreach ((array) $request->input('tag_names', []) as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $slug = $this->slugify($name);
            $tag = Tag::query()
                ->where(function ($q) use ($slug, $name) {
                    $q->where('slug', $slug)->orWhere('name', $name);
                })
                ->first();
            if (! $tag) {
                $tag = Tag::create([
                    'name' => $name,
                    'slug' => $slug,
                    'status' => 'published',
                    'sort_order' => 0,
                ]);
            }
            $ids->push($tag->id);
        }

        $article->tags()->sync($ids->unique()->values()->all());
    }

    private function mostUsedTags()
    {
        return Tag::query()
            ->withCount('articles')
            ->orderByDesc('articles_count')
            ->orderBy('name')
            ->limit(20)
            ->get();
    }
}
