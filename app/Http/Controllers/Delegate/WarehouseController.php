<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    /**
     * Display a listing of warehouses accessible to the delegate
     */
    public function index()
    {
        $warehouses = Auth::user()->warehouses()->with('creator')->paginate(10);

        return view('delegate.warehouses.index', compact('warehouses'));
    }
}
