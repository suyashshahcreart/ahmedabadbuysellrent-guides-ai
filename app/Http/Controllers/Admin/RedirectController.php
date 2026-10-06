<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:seo.redirects.view|redirects.manage')->only(['index']);
        $this->middleware('permission:seo.redirects.create|redirects.manage')->only(['create', 'store']);
        $this->middleware('permission:seo.redirects.delete|redirects.manage')->only(['destroy']);
    }

    public function index()
    {
        return view('admin.redirects.index', ['redirects' => Redirect::latest()->get()]);
    }

    public function create()
    {
        return view('admin.redirects.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_path' => 'required|string',
            'to_url' => 'required|string',
            'status_code' => 'required|in:301,302',
            'notes' => 'nullable',
        ]);
        $data['from_path'] = '/'.ltrim($data['from_path'], '/');
        $data['active'] = $request->boolean('active', true);
        Redirect::create($data);

        return redirect()->route('administrator.redirects.index')->with('status', 'Redirect added.');
    }

    public function destroy(Redirect $redirect)
    {
        $redirect->delete();

        return redirect()->route('administrator.redirects.index')->with('status', 'Redirect deleted.');
    }
}
