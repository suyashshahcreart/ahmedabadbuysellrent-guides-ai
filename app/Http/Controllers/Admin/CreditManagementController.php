<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiCreditLedger;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class CreditManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ai.credits.view')->only(['index', 'history', 'usage']);
        $this->middleware('permission:ai.credits.manage')->only(['users', 'update']);
    }

    public function index(Request $request)
    {
        $users = User::with('roles')->withSum([
            'aiUsageLogs as credits_used_this_month' => function ($q) {
                $q->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month);
            },
        ], 'credits')->latest()->get();

        $roles = Role::orderBy('name')->get();

        return view('admin.ai.credits.index', compact('users', 'roles'));
    }

    public function users()
    {
        $users = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['superAdmin', 'admin']))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'credit_amount' => 'required|integer|min:0|max:99999',
            'forced' => 'boolean',
            'note' => 'nullable|string|max:255',
        ]);
        $user = User::findOrFail($data['user_id']);
        if (! $user->requiresAiCreditCheck() && ! $request->boolean('forced')) {
            return response()->json(['success' => false, 'message' => 'Administrators are excluded from AI credit limits unless forced.'], 422);
        }
        $old = (int) $user->ai_monthly_credit;
        $user->ai_monthly_credit = $data['credit_amount'];
        $user->save();
        AiCreditLedger::create([
            'user_id' => $user->id,
            'actor_id' => $request->user()->id,
            'old_credit' => $old,
            'new_credit' => $data['credit_amount'],
            'forced' => $request->boolean('forced'),
            'note' => $data['note'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'AI credits updated.']);
    }

    public function history(User $user)
    {
        $creditHistory = $user->creditLedgers()->with('actor')->latest()->get();

        return view('admin.ai.credits.credit-history', compact('user', 'creditHistory'));
    }

    public function usage(User $user)
    {
        $aiUsageHistory = $user->aiUsageLogs()->latest()->get();
        $used = (int) $user->aiUsageLogs()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('credits');
        $remainingCredits = max(0, (int) $user->ai_monthly_credit - $used);

        return view('admin.ai.credits.ai-usage-history', compact('user', 'aiUsageHistory', 'remainingCredits'));
    }
}
