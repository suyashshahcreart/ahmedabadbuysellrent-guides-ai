<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SavesSeo;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use SavesSeo;

    public function __construct()
    {
        $this->middleware('permission:categories.view|categories.manage')->only(['index']);
        $this->middleware('permission:categories.create|categories.manage')->only(['create', 'store']);
        $this->middleware('permission:categories.edit|categories.manage')->only(['edit', 'update', 'reorder', 'toggleStatus']);
        $this->middleware('permission:categories.delete|categories.manage')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $query = Category::with('parent')->withCount('articles');

        if ($status === 'active') {
            $query->where('status', 'published');
        } elseif ($status === 'inactive') {
            $query->where('status', '!=', 'published');
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.categories.index', compact('categories', 'status'));
    }

    public function create()
    {
        return view('admin.categories.form', [
            'category' => new Category(['status' => 'published', 'sort_order' => 0]),
            'parents' => Category::whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $c = new Category();
        $this->persist($c, $request);

        return redirect()->route('administrator.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category)
    {
        $category->load(['seo', 'faqs']);

        return view('admin.categories.form', [
            'category' => $category,
            'parents' => Category::whereNull('parent_id')
                ->where('id', '!=', $category->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $this->persist($category, $request);

        return redirect()->route('administrator.categories.index')->with('status', 'Category saved.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('administrator.categories.index')->with('status', 'Category deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids) || $ids === []) {
            return response()->json(['success' => false, 'message' => 'No ids.'], 422);
        }

        foreach ($ids as $i => $id) {
            Category::whereKey((int) $id)->update(['sort_order' => $i + 1]);
        }

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    public function toggleStatus(Category $category): JsonResponse
    {
        $category->status = $category->status === 'published' ? 'draft' : 'published';
        $category->save();

        $active = $category->status === 'published';

        return response()->json([
            'success' => true,
            'is_active' => $active,
            'message' => $active ? 'Category is now active.' : 'Category is now inactive.',
        ]);
    }

    private function persist(Category $c, Request $request): void
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'nav_label' => 'nullable|string|max:255',
            'tag_label' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:published,draft',
        ]);
        $data['slug'] = $data['slug'] ?: $this->slugify($data['name']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        if (! empty($data['parent_id']) && (int) $data['parent_id'] === (int) $c->id) {
            $data['parent_id'] = null;
        }
        $c->fill($data)->save();
        $this->saveSeo($c, $request);
        $this->saveFaqs($c, $request);
    }
}
