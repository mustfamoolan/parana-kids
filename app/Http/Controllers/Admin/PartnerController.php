<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    /**
     * عرض صفحة الشركاء (Placeholder - قيد التطوير)
     */
    public function index()
    {
        return view('admin.partners.index');
    }
}
