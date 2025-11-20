<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * عرض صفحة الإعدادات
     */
    public function index()
    {
        return view('delegate.settings.index');
    }
}

