<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivateWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrivateWarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', PrivateWarehouse::class);

        $warehouses = PrivateWarehouse::with(['creator', 'users'])
            ->withCount(['users', 'invoiceProducts', 'invoices'])
            ->latest()
            ->get();

        return view('admin.private-warehouses.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', PrivateWarehouse::class);

        return view('admin.private-warehouses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', PrivateWarehouse::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();

        PrivateWarehouse::create($validated);

        return redirect()->route('admin.private-warehouses.index')
            ->with('success', 'تم إنشاء المخزن الخاص بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(PrivateWarehouse $privateWarehouse)
    {
        $this->authorize('view', $privateWarehouse);

        // إعادة التوجيه إلى صفحة الفواتير مع فلترة حسب المخزن الخاص
        return redirect()->route('admin.invoices.index', ['private_warehouse_id' => $privateWarehouse->id]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PrivateWarehouse $privateWarehouse)
    {
        $this->authorize('update', $privateWarehouse);

        return view('admin.private-warehouses.edit', compact('privateWarehouse'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PrivateWarehouse $privateWarehouse)
    {
        $this->authorize('update', $privateWarehouse);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $privateWarehouse->update($validated);

        return redirect()->route('admin.private-warehouses.index')
            ->with('success', 'تم تحديث المخزن الخاص بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PrivateWarehouse $privateWarehouse)
    {
        $this->authorize('delete', $privateWarehouse);

        // التحقق من وجود مستخدمين مرتبطين
        if ($privateWarehouse->users()->count() > 0) {
            return back()->withErrors(['error' => 'لا يمكن حذف المخزن الخاص لأنه مرتبط بمستخدمين']);
        }

        $privateWarehouse->delete();

        return redirect()->route('admin.private-warehouses.index')
            ->with('success', 'تم حذف المخزن الخاص بنجاح');
    }
}
