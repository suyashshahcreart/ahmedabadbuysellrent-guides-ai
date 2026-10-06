<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:faqs.view|faq.manage')->only(['index']);
        $this->middleware('permission:faqs.create|faq.manage')->only(['store']);
        $this->middleware('permission:faqs.edit|faq.manage')->only(['update', 'toggleStatus']);
        $this->middleware('permission:faqs.delete|faq.manage')->only(['destroy']);
    }

    public function index()
    {
        $faqs = Faq::query()->with('faqable')->latest()->get();

        return view('admin.faqs.index', [
            'faqs' => $faqs,
            'rows' => $faqs->map(fn (Faq $faq) => $this->row($faq)),
        ]);
    }

    public function store(Request $request)
    {
        Faq::create($request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]) + ['status' => true, 'faqable_id' => null, 'faqable_type' => null]);

        return back()->with('status', 'FAQ added.');
    }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'FAQ updated.']);
        }

        return back()->with('status', 'FAQ updated.');
    }

    public function toggleStatus(Request $request, Faq $faq)
    {
        $faq->status = $request->boolean('status', ! $faq->status);
        $faq->save();

        return response()->json(['success' => true, 'status' => $faq->status]);
    }

    public function destroy(Request $request, Faq $faq)
    {
        $faq->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'FAQ deleted.']);
        }

        return back()->with('status', 'FAQ deleted.');
    }

    private function row(Faq $faq): array
    {
        $related = $faq->faqable;
        $type = $faq->faqable_type ? class_basename($faq->faqable_type) : null;
        $label = match ($type) {
            'Article' => 'Guide: '.($related->title ?? '#'.$faq->faqable_id),
            'Page' => 'Page: '.($related->title ?? '#'.$faq->faqable_id),
            'Category' => 'Category: '.($related->name ?? $related->title ?? '#'.$faq->faqable_id),
            'Locality' => 'Locality: '.($related->name ?? '#'.$faq->faqable_id),
            default => $faq->faqable_id ? ($type ?: 'Attached').': #'.$faq->faqable_id : 'Global / Homepage',
        };

        return [
            'id' => $faq->id,
            'entity' => $label,
            'question' => $faq->question,
            'answer' => (string) $faq->answer,
            'sort_order' => (int) $faq->sort_order,
            'status' => (bool) $faq->status,
            'created_at' => optional($faq->created_at)?->format('d M Y'),
        ];
    }
}
