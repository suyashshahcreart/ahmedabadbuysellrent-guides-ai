<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:permissions.view')->only(['index', 'show']);
        $this->middleware('permission:permissions.create')->only(['create', 'store']);
        $this->middleware('permission:permissions.edit')->only(['edit', 'update']);
        $this->middleware('permission:permissions.delete')->only(['destroy']);
    }

    public function index()
    {
        $permissions = Permission::withCount(['roles', 'users'])->orderBy('name')->get();

        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|min:2|max:255|unique:permissions,name',
        ]);
        Permission::create(['name' => $data['name']]);

        return redirect()->route('administrator.permissions.index')->with('status', 'Permission created. Update sidebar @can if this is a new gate.');
    }

    public function show(Permission $permission)
    {
        $roles = $permission->roles;
        $users = $permission->users;

        return view('admin.permissions.show', compact('permission', 'roles', 'users'));
    }

    public function edit(Permission $permission)
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name' => 'required|min:2|max:255|unique:permissions,name,'.$permission->id,
        ]);
        $permission->update(['name' => $data['name']]);

        return redirect()->route('administrator.permissions.index')->with('status', 'Permission renamed. Check @can directives.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('administrator.permissions.index')->with('status', 'Permission deleted.');
    }
}
