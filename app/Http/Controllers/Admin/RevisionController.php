<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Revision;

class RevisionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:articles.edit|pages.edit|pages.manage');
    }
    public function show(Revision $revision)
    {
        return view('admin.revisions.show', compact('revision'));
    }

    public function restore(Revision $revision)
    {
        $model = $revision->revisable;
        $payload = $revision->payload;
        unset($payload['id'], $payload['created_at'], $payload['updated_at']);
        $model->fill($payload)->save();

        return back()->with('status', 'Revision restored.');
    }
}
