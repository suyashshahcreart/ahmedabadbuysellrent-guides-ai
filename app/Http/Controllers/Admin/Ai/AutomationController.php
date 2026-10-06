<?php

namespace App\Http\Controllers\Admin\Ai;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateGuideDraftJob;
use App\Models\AiAngle;
use App\Models\AiGenerationLog;
use App\Models\AiGenerationRun;
use App\Models\AiGeoNode;
use App\Models\AiSetting;
use App\Models\AiStructureTemplate;
use App\Models\AiTopicQueue;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Services\Ai\AiScheduler;
use App\Services\Ai\TopicPlanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AutomationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ai.automation.view')->only([
            'index', 'reports', 'showRun', 'templates', 'geo', 'editors',
        ]);
        $this->middleware('permission:ai.automation.manage')->only([
            'update', 'start', 'pause', 'generateOnce', 'updateTemplate', 'ensureEditors', 'seedPools',
            'updateRunTarget', 'startRun', 'stopRun',
        ]);
    }

    public function index()
    {
        $settings = AiSetting::current();
        $settings->load('activeRun', 'defaultCategory');
        $totalAi = Article::query()->where('source', 'ai')->count();
        $running = AiTopicQueue::query()->where('status', 'running')->count();
        $failed = AiTopicQueue::query()->where('status', 'failed')->count();
        $duplicates = AiTopicQueue::query()->where('status', 'duplicate')->count();
        $recentLogs = AiGenerationLog::query()->latest('id')->limit(15)->with(['article', 'topic'])->get();
        $categories = Category::query()->orderBy('name')->get();

        return view('admin.ai.automation.index', compact(
            'settings', 'totalAi', 'running', 'failed', 'duplicates', 'recentLogs', 'categories'
        ));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'interval' => 'required|in:2h,4h,8h,daily,every_5_days,monthly',
            'target_total' => 'required|integer|min:1|max:100000',
            'publish_percent' => 'required|integer|min:0|max:100',
            'model' => 'required|string|max:120',
            'max_tokens' => 'required|integer|min:1000|max:16000',
            'min_words' => 'required|integer|min:600|max:4000',
            'default_category_id' => 'nullable|exists:categories,id',
            'system_prompt_extra' => 'nullable|string|max:10000',
            'research_notes' => 'nullable|string|max:10000',
        ]);

        $settings = AiSetting::current();
        $settings->fill($data);
        $settings->save();

        return back()->with('status', 'Automation settings saved.');
    }

    public function start(Request $request)
    {
        $settings = AiSetting::current();
        $scheduler = app(AiScheduler::class);

        $settings->enabled = true;
        $settings->next_tick_at = now();
        $settings->save();

        // Resume older incomplete runs and open a NEW history run.
        // Old runs stay active until THEIR target is finished (not closed on Start).
        $scheduler->resumePausedRuns();
        $run = $scheduler->beginNewSession($settings->fresh());
        $result = $scheduler->tick(forceImmediate: true, preferRunId: $run->id);
        $settings = AiSetting::current();

        $next = optional($settings->next_tick_at)->format('Y-m-d H:i:s') ?: '—';
        if ($result?->status === 'queued') {
            return back()->with(
                'status',
                "Started run #{$run->id}. {$result->message} Older incomplete runs stay active in background."
            );
        }

        $message = $result?->message ?: 'Automation enabled.';

        return back()->with(
            in_array($result?->status, ['busy', 'waiting'], true) ? 'status' : 'error',
            "Run #{$run->id} opened. {$message} Next cycle: {$next}"
        );
    }

    public function pause()
    {
        $settings = AiSetting::current();
        $settings->enabled = false;
        $settings->save();

        app(AiScheduler::class)->pauseAllOpenRuns();

        return back()->with('status', 'Automation paused. Open runs marked paused (not completed).');
    }

    public function generateOnce(Request $request, TopicPlanner $planner)
    {
        $settings = AiSetting::current();
        $run = AiGenerationRun::query()->create([
            'status' => 'running',
            'interval_snapshot' => 'manual',
            'target' => 1,
            'started_at' => now(),
            'queued' => 1,
            'running' => 1,
        ]);

        try {
            $topic = $planner->plan($run);
        } catch (\Throwable $e) {
            $run->update(['status' => 'failed', 'failed' => 1, 'running' => 0, 'finished_at' => now()]);

            return back()->with('error', $e->getMessage());
        }

        $topic->update(['status' => 'running', 'attempts' => 1]);
        GenerateGuideDraftJob::dispatch($topic->id, $request->user()->id)->onQueue('ai-generate');

        return back()->with('status', 'Queued one AI article: '.$topic->title_hint);
    }

    public function reports()
    {
        app(AiScheduler::class)->syncAllRunStatuses();

        $settings = AiSetting::current();
        $totalAi = Article::query()->where('source', 'ai')->count();
        $publishedAi = Article::query()->where('source', 'ai')->where('status', 'published')->count();
        $draftAi = Article::query()->where('source', 'ai')->where('status', 'draft')->count();
        $running = AiTopicQueue::query()->where('status', 'running')->count();
        $remaining = max(0, (int) $settings->target_total - $totalAi);
        $tokenIn = (int) AiGenerationLog::query()->sum('input_tokens');
        $tokenOut = (int) AiGenerationLog::query()->sum('output_tokens');
        $runs = AiGenerationRun::query()->latest('id')->paginate(20);
        $recentArticles = Article::query()
            ->where('source', 'ai')
            ->latest('id')
            ->with(['category', 'author'])
            ->limit(25)
            ->get();

        return view('admin.ai.automation.reports', compact(
            'settings', 'totalAi', 'publishedAi', 'draftAi', 'running', 'remaining',
            'tokenIn', 'tokenOut', 'runs', 'recentArticles'
        ));
    }

    public function showRun(AiGenerationRun $run)
    {
        $run->refreshLifecycleStatus();
        $run->loadCount(['topics', 'logs', 'articles']);
        $topics = $run->topics()->latest('id')->with(['article', 'geoNode', 'angle', 'category'])->paginate(30);
        $logs = $run->logs()->latest('id')->limit(50)->get();
        $settings = AiSetting::current()->load('defaultCategory');

        // Prefer snapshot captured at run start; fall back to live settings.
        $snapshot = $run->snapshot();
        if ($snapshot === []) {
            $snapshot = $settings->toSnapshot();
            $run->settings_snapshot = $snapshot;
            $run->save();
        }

        $categoriesUsed = $run->topics()
            ->with('category')
            ->get()
            ->pluck('category')
            ->filter()
            ->unique('id')
            ->values();

        return view('admin.ai.automation.run', compact(
            'run', 'topics', 'logs', 'settings', 'snapshot', 'categoriesUsed'
        ));
    }

    public function startRun(AiGenerationRun $run)
    {
        if ($run->hasMetTarget()) {
            return back()->with('error', "Run #{$run->id} already finished its target ({$run->completed}/{$run->target}).");
        }

        $settings = AiSetting::current();
        $settings->enabled = true;
        $settings->active_run_id = $run->id;
        if (! $settings->next_tick_at || $settings->next_tick_at->isFuture()) {
            // Keep global clock, but still allow an immediate post for this run.
        }
        $settings->save();

        if (! $run->settings_snapshot) {
            $run->settings_snapshot = $settings->toSnapshot();
        }
        $run->status = 'active';
        $run->finished_at = null;
        $run->save();

        $result = app(AiScheduler::class)->tick(forceImmediate: true, preferRunId: $run->id);

        return back()->with(
            'status',
            $result?->message ?: "Run #{$run->id} started (active)."
        );
    }

    public function stopRun(AiGenerationRun $run)
    {
        if ($run->hasMetTarget()) {
            $run->status = 'completed';
            $run->running = 0;
            $run->finished_at = $run->finished_at ?: now();
            $run->save();

            return back()->with('status', "Run #{$run->id} already completed its target.");
        }

        // Stop only this run — other active runs keep working.
        AiTopicQueue::query()
            ->where('run_id', $run->id)
            ->where('status', 'running')
            ->update([
                'status' => 'failed',
                'error' => 'Stopped by admin from run page.',
            ]);

        $run->status = 'paused';
        $run->running = 0;
        $run->save();

        $settings = AiSetting::current();
        if ((int) $settings->active_run_id === (int) $run->id) {
            $next = AiGenerationRun::query()->open()->where('id', '!=', $run->id)->latest('id')->first();
            $settings->active_run_id = $next?->id;
            $settings->save();
        }

        return back()->with('status', "Run #{$run->id} stopped. Automatic generation for this run is paused.");
    }

    public function updateRunTarget(Request $request, AiGenerationRun $run)
    {
        $data = $request->validate([
            'target' => 'required|integer|min:1|max:100000',
        ]);

        $run->target = (int) $data['target'];
        if ($run->hasMetTarget()) {
            $run->status = 'completed';
            $run->finished_at = $run->finished_at ?: now();
        } elseif ($run->status === 'completed') {
            $run->status = 'active';
            $run->finished_at = null;
        }
        $run->save();
        $run->refreshLifecycleStatus();

        $pending = max(0, (int) $run->target - (int) $run->completed);

        return back()->with(
            'status',
            "Run #{$run->id} target updated: {$run->target}/{$run->completed} (pending {$pending})."
        );
    }

    public function templates()
    {
        $templates = AiStructureTemplate::query()->orderBy('id')->get();

        return view('admin.ai.automation.templates', compact('templates'));
    }

    public function updateTemplate(Request $request, AiStructureTemplate $template)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'notes' => 'nullable|string|max:5000',
            'sections_text' => 'required|string',
            'keyword_rules_json' => 'required|string',
            'status' => 'sometimes|boolean',
        ]);

        $sections = collect(preg_split('/\r\n|\r|\n/', $data['sections_text']))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->values()
            ->all();
        $rules = json_decode($data['keyword_rules_json'], true);
        if (! is_array($rules)) {
            return back()->with('error', 'Keyword rules must be valid JSON.');
        }

        $template->update([
            'name' => $data['name'],
            'notes' => $data['notes'] ?? null,
            'sections' => $sections,
            'keyword_rules' => $rules,
            'status' => $request->boolean('status', true),
        ]);

        return back()->with('status', 'Template updated.');
    }

    public function geo()
    {
        $nodes = AiGeoNode::query()->with('parent')->orderBy('type')->orderBy('sort_order')->orderBy('name')->get();
        $angles = AiAngle::query()->orderBy('sort_order')->get();

        return view('admin.ai.automation.geo', compact('nodes', 'angles'));
    }

    public function editors()
    {
        $slugs = [
            'ananya-mehta', 'rohan-patel', 'kavya-shah', 'harsh-trivedi', 'isha-desai',
            'manav-joshi', 'priya-chauhan', 'devansh-raval', 'sneha-pandya', 'aarav-solanki',
        ];
        $editors = Author::query()->whereIn('slug', $slugs)->withCount(['articles' => fn ($q) => $q->where('source', 'ai')])->orderBy('name')->get();

        return view('admin.ai.automation.editors', compact('editors'));
    }

    public function ensureEditors()
    {
        Artisan::call('guides:seed-ai-automation');

        return back()->with('status', 'Editors and pools re-seeded.');
    }

    public function seedPools()
    {
        Artisan::call('guides:seed-ai-automation');

        return back()->with('status', trim(Artisan::output()) ?: 'Pools seeded.');
    }
}
