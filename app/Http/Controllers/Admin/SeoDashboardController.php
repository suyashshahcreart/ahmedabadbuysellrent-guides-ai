<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SeoAuditService;

class SeoDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:seo.view|seo.manage');
    }

    public function audit(SeoAuditService $audit)
    {
        return view('admin.seo.dashboard', $audit->run());
    }
}
