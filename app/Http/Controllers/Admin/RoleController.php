<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:roles.view')->only(['index', 'show']);
        $this->middleware('permission:roles.create')->only(['create', 'store']);
        $this->middleware('permission:roles.edit')->only(['edit', 'update']);
        $this->middleware('permission:roles.delete')->only(['destroy']);
    }

    public function index()
    {
        $roles = Role::withCount('users')->with('permissions')->orderBy('name')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create', [
            'groupedPermissions' => collect(PermissionCatalog::groups()),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|min:2|max:255|regex:/^[A-Za-z0-9_-]+$/|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);
        $role = Role::create(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('administrator.roles.index')->with('status', 'Role created.');
    }

    public function show(Role $role)
    {
        $role->load('permissions', 'users');

        return view('admin.roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'groupedPermissions' => collect(PermissionCatalog::groups()),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'required|min:2|max:255|regex:/^[A-Za-z0-9_-]+$/|unique:roles,name,'.$role->id,
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('administrator.roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'superAdmin') {
            return back()->with('error', 'Cannot delete superAdmin.');
        }
        $role->delete();

        return redirect()->route('administrator.roles.index')->with('status', 'Role deleted.');
    }
}
