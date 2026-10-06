<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:menus.view')->only(['index']);
        $this->middleware('permission:menus.edit')->only(['storeItem', 'destroyItem']);
    }
    public function index()
    {
        $menus = Menu::with('allItems')->get();

        return view('admin.menus.index', compact('menus'));
    }

    public function storeItem(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'label' => 'required',
            'url' => 'required',
            'target' => 'nullable',
            'sort_order' => 'nullable|integer',
        ]);
        $data['target'] = $data['target'] ?? '_self';
        $data['status'] = true;
        $menu->allItems()->create($data);

        return back();
    }

    public function destroyItem(MenuItem $item)
    {
        $item->delete();

        return back();
    }
}
