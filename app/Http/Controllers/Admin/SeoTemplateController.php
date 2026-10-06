<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoTemplate;
use Illuminate\Http\Request;

class SeoTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:seo.templates.manage|seo.view')->only(['index', 'show']);
        $this->middleware('permission:seo.templates.manage|seo.create')->only(['create', 'store']);
        $this->middleware('permission:seo.templates.manage|seo.edit')->only(['edit', 'update']);
        $this->middleware('permission:seo.templates.manage|seo.delete')->only(['destroy']);
    }

    public function index()
    {
        $templates = SeoTemplate::query()->latest()->get();

        return view('admin.seo.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.seo.templates.create', ['template' => new SeoTemplate(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        SeoTemplate::create($this->validated($request));

        return redirect()->route('administrator.seo.templates.index')->with('status', 'Template created.');
    }

    public function edit(SeoTemplate $template)
    {
        return view('admin.seo.templates.edit', compact('template'));
    }

    public function update(Request $request, SeoTemplate $template)
    {
        $template->update($this->validated($request));

        return redirect()->route('administrator.seo.templates.index')->with('status', 'Template updated.');
    }

    public function destroy(SeoTemplate $template)
    {
        $template->delete();

        return redirect()->route('administrator.seo.templates.index')->with('status', 'Template deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'page_type' => 'required|string|in:'.implode(',', array_keys(config('seo.page_types'))),
            'model_type' => 'nullable|string|max:255',
            'title_template' => 'nullable|string|max:255',
            'description_template' => 'nullable|string',
            'h1_template' => 'nullable|string|max:255',
            'schema_type' => 'nullable|string|max:80',
            'schema_template_json' => 'nullable|string',
            'default_robots' => 'nullable|string|max:80',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        if (! empty($data['schema_template_json'])) {
            json_decode($data['schema_template_json']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'schema_template_json' => 'Schema JSON is invalid.',
                ]);
            }
        }

        return $data;
    }
}
