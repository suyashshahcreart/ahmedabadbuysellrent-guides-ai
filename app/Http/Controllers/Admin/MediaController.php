<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Services\MediaService;
use App\Support\MediaLibraryUi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:media.view|media.manage')->only(['index', 'json', 'show', 'next', 'previous']);
        $this->middleware('permission:media.upload|media.manage')->only(['store']);
        $this->middleware('permission:media.edit|media.manage')->only(['update']);
        $this->middleware('permission:media.delete|media.manage')->only(['destroy']);
    }

    public function index()
    {
        return view('admin.media.index', [
            'mime_types' => MediaLibraryUi::typeGroups(),
            'dates' => MediaLibraryUi::dateOptions(),
        ]);
    }

    public function json(Request $request)
    {
        $q = MediaAsset::query();
        $this->applySearch($q, $request->input('search', $request->query('s')));
        $this->applyType($q, (string) $request->input('type', ''));
        $this->applyDate($q, (string) $request->input('date', ''));
        $this->applySort($q, (string) $request->input('sort', 'newest'));

        $perPage = max(1, min(60, (int) $request->input('per_page', 18)));
        $paginator = $q->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $paginator->getCollection()->map(fn (MediaAsset $m) => $this->toItem($m))->values()->all(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function show(MediaAsset $mediaAsset)
    {
        return response()->json([
            'success' => true,
            'data' => $this->toItem($mediaAsset),
        ]);
    }

    public function next(MediaAsset $mediaAsset)
    {
        $next = MediaAsset::query()
            ->where('id', '>', $mediaAsset->id)
            ->orderBy('id')
            ->first();

        return response()->json([
            'success' => (bool) $next,
            'media' => $next ? $this->toItem($next) : null,
        ]);
    }

    public function previous(MediaAsset $mediaAsset)
    {
        $prev = MediaAsset::query()
            ->where('id', '<', $mediaAsset->id)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'success' => (bool) $prev,
            'media' => $prev ? $this->toItem($prev) : null,
        ]);
    }

    public function store(Request $request, MediaService $media)
    {
        $request->validate([
            'file' => 'required|file|max:102400|mimes:jpg,jpeg,png,webp,gif,avif,svg,ico,pdf,mp4,webm,mp3',
            'alt' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
        ]);
        $item = $media->storeUploaded($request->file('file'), $request->input('alt') ?: $request->input('title'));

        if ($this->wantsPickerJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Uploaded.',
                'item' => $this->toItem($item),
                'data' => $this->toItem($item),
            ]);
        }

        return back()->with('status', 'Uploaded.');
    }

    public function update(Request $request, MediaAsset $mediaAsset)
    {
        $data = $request->validate([
            'alt' => 'nullable|string',
            'alt_text' => 'nullable|string',
            'caption' => 'nullable|string',
            'description' => 'nullable|string',
            'title' => 'nullable|string|max:255',
        ]);
        if (! empty($data['alt_text']) && empty($data['alt'])) {
            $data['alt'] = $data['alt_text'];
        }
        unset($data['alt_text'], $data['title']);
        $mediaAsset->update($data);

        if ($this->wantsPickerJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Media details updated successfully',
                'data' => $this->toItem($mediaAsset->fresh()),
            ]);
        }

        return back()->with('status', 'Updated.');
    }

    public function destroy(Request $request, MediaAsset $mediaAsset)
    {
        try {
            Storage::disk($mediaAsset->disk ?: 'public')->delete(array_filter([
                $mediaAsset->path,
                $mediaAsset->webp_path,
            ]));
        } catch (\Throwable $e) {
            // keep going — DB row still needs to go
        }
        $mediaAsset->delete();

        if ($this->wantsPickerJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Media deleted.',
            ]);
        }

        return back()->with('status', 'Deleted.');
    }

    private function wantsPickerJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->wantsJson()
            || $request->ajax()
            || str_contains((string) $request->header('Accept'), 'application/json')
            || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    private function applySearch($q, ?string $s): void
    {
        $s = trim((string) $s);
        if ($s === '') {
            return;
        }
        $q->where(function ($qq) use ($s) {
            $qq->where('original_name', 'like', '%'.$s.'%')
                ->orWhere('alt', 'like', '%'.$s.'%')
                ->orWhere('caption', 'like', '%'.$s.'%')
                ->orWhere('description', 'like', '%'.$s.'%');
        });
    }

    private function applyType($q, string $type): void
    {
        if ($type === '' || $type === 'all') {
            return;
        }
        if ($type === 'image') {
            $q->where('mime_type', 'like', 'image/%');

            return;
        }
        if ($type === 'video') {
            $q->where('mime_type', 'like', 'video/%');

            return;
        }
        if ($type === 'pdf') {
            $q->where('mime_type', 'application/pdf');

            return;
        }
        $q->where('mime_type', $type);
    }

    private function applyDate($q, string $date): void
    {
        if ($date === '') {
            return;
        }
        if ($date === 'today') {
            $q->whereDate('created_at', Carbon::today());

            return;
        }
        if ($date === 'last7days') {
            $q->where('created_at', '>=', Carbon::now()->subDays(7));

            return;
        }
        if ($date === 'last30days') {
            $q->where('created_at', '>=', Carbon::now()->subDays(30));

            return;
        }
        if ($date === 'thismonth') {
            $q->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);

            return;
        }
        if ($date === 'lastmonth') {
            $q->whereBetween('created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);

            return;
        }
        if (preg_match('/^\d{4}-\d{2}$/', $date)) {
            $start = Carbon::createFromFormat('Y-m', $date)->startOfMonth();
            $q->whereBetween('created_at', [$start, $start->copy()->endOfMonth()]);
        }
    }

    private function applySort($q, string $sort): void
    {
        match ($sort) {
            'oldest' => $q->orderBy('created_at'),
            'name' => $q->orderBy('original_name'),
            'name_desc' => $q->orderByDesc('original_name'),
            default => $q->latest(),
        };
    }

    private function toItem(MediaAsset $m): array
    {
        $title = pathinfo((string) $m->original_name, PATHINFO_FILENAME) ?: $m->original_name;
        $iso = optional($m->created_at)?->toIso8601String();
        $dimensions = ($m->width && $m->height) ? $m->width.' × '.$m->height : null;

        return [
            'id' => $m->id,
            'ID' => $m->id,
            'url' => $m->url(),
            'thumb_url' => $m->webpUrl() ?: $m->url(),
            'title' => $title,
            'filename' => $m->original_name,
            'file_name' => $m->original_name,
            'mime_type' => $m->mime_type,
            'alt' => $m->alt,
            'alt_text' => $m->alt,
            'caption' => $m->caption,
            'description' => $m->description,
            'width' => $m->width,
            'height' => $m->height,
            'file_size' => (int) $m->size,
            'size' => $this->humanSize((int) $m->size),
            'dimensions' => $dimensions,
            'date' => $iso,
            'created_at' => $iso,
            'uploaded_at' => $iso,
            'uploaded_by' => 'Library',
            'is_360' => false,
            'usages' => [],
            'metadata' => [
                'width' => $m->width,
                'height' => $m->height,
            ],
        ];
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
