<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\MediaAsset;
use App\Models\Page;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'articles' => Article::count(),
            'published' => Article::where('status', 'published')->count(),
            'scheduled' => Article::where('status', 'scheduled')->count(),
            'pages' => Page::count(),
            'media' => MediaAsset::count(),
        ]);
    }

    public function profile()
    {
        return view('admin.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email',
            'password' => 'nullable|min:8|confirmed',
        ]);
        $user = $request->user();
        $user->fill(['name' => $data['name'], 'email' => $data['email']]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return back()->with('status', 'Profile updated.');
    }
}
