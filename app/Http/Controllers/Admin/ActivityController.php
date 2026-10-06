<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:activity.view');
    }
    public function index()
    {
        return view('admin.activity.index', ['logs' => Activity::latest()->limit(500)->get()]);
    }
}
