<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivateWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileAdminPrivateWarehouseController extends Controller
{
    /**
     * جلب قائمة المخازن الخاصة مع البحث والترقيم
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح للوصول لهذه البيانات.',
            ], 403);
        }

        try {
            $query = PrivateWarehouse::with(['creator'])
                ->withCount(['users', 'invoiceProducts', 'invoices']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $perPage = $request->input('per_page', 20);
            $warehouses = $query->orderBy('name')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'private_warehouses' => $warehouses
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المخازن الخاصة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إنشاء مخزن خاص جديد
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإجراء هذه العملية.',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            $warehouse = PrivateWarehouse::create([
                'name' => $request->name,
                'description' => $request->description,
                'created_by' => $currentUser->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المخزن الخاص بنجاح.',
                'data' => [
                    'warehouse' => PrivateWarehouse::with(['creator'])
                        ->withCount(['users', 'invoiceProducts', 'invoices'])
                        ->find($warehouse->id)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء المخزن الخاص: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحديث بيانات مخزن خاص
     */
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإجراء هذه العملية.',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            $warehouse = PrivateWarehouse::findOrFail($id);
            $warehouse->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المخزن الخاص بنجاح.',
                'data' => [
                    'warehouse' => PrivateWarehouse::with(['creator'])
                        ->withCount(['users', 'invoiceProducts', 'invoices'])
                        ->find($warehouse->id)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المخزن الخاص: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف مخزن خاص
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإجراء هذه العملية.',
            ], 403);
        }

        try {
            $warehouse = PrivateWarehouse::findOrFail($id);

            // التحقق من وجود مستخدمين مرتبطين
            if ($warehouse->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف المخزن الخاص لأنه مرتبط بمستخدمين.',
                ], 400);
            }

            $warehouse->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المخزن الخاص بنجاح.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المخزن الخاص: ' . $e->getMessage(),
            ], 500);
        }
    }
}
