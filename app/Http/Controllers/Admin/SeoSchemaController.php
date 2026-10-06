<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoSchema;

class SeoSchemaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:seo.schemas.view');
    }

    public function index()
    {
        $schemas = SeoSchema::query()->with('schemaable')->latest()->get();

        return view('admin.seo.schemas.index', compact('schemas'));
    }
}
