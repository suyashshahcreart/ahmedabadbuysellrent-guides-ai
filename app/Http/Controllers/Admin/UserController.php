<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users.view|users.manage')->only(['index', 'show']);
        $this->middleware('permission:users.create|users.manage')->only(['create', 'store']);
        $this->middleware('permission:users.edit|users.manage')->only(['edit', 'update']);
        $this->middleware('permission:users.delete|users.manage')->only(['destroy']);
    }

    public function index()
    {
        $users = User::with('roles')->withCount('roles')->latest()->get();
        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        return view('admin.users.create', ['roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->syncRoles($data['roles'] ?? []);

        return redirect()->route('administrator.users.index')->with('status', 'User created.');
    }

    public function show(User $user)
    {
        $user->load('roles', 'permissions');

        return view('admin.users.show', [
            'user' => $user,
            'effective' => $user->getAllPermissions()->sortBy('name'),
        ]);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|min:8',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);
        $user->fill(['name' => $data['name'], 'email' => $data['email']]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();
        $user->syncRoles($data['roles'] ?? []);

        return redirect()->route('administrator.users.index')->with('status', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->hasRole('superAdmin') && User::role('superAdmin')->count() <= 1) {
            return back()->with('error', 'Cannot delete the last superAdmin.');
        }
        $user->delete();

        return redirect()->route('administrator.users.index')->with('status', 'User deleted.');
    }
}
