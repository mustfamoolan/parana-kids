<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhoneContact;
use App\Models\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileAdminPhoneBookController extends Controller
{
    /**
     * جلب قائمة جهات الاتصال مع البحث والترقيم
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();

        if (!$currentUser || (!$currentUser->isAdmin() && !$currentUser->isSupplier())) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح للوصول لهذه البيانات.',
            ], 403);
        }

        try {
            $query = PhoneContact::with('phoneNumbers');

            // البحث على الاسم أو رقم الهاتف
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhereHas('phoneNumbers', function($phoneQuery) use ($search) {
                          $phoneQuery->where('phone_number', 'like', "%{$search}%");
                      });
                });
            }

            $perPage = $request->input('per_page', 20);
            $contacts = $query->orderBy('name')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'contacts' => $contacts
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب دليل الهاتف: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إضافة جهة اتصال جديدة مع الرقم الأول
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        if (!$currentUser || (!$currentUser->isAdmin() && !$currentUser->isSupplier())) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإجراء هذه العملية.',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
        ]);

        try {
            $contact = PhoneContact::create([
                'name' => $request->name,
            ]);

            PhoneNumber::create([
                'contact_id' => $contact->id,
                'phone_number' => $request->phone_number,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الشخص بنجاح.',
                'data' => [
                    'contact' => PhoneContact::with('phoneNumbers')->find($contact->id)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الشخص: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إضافة رقم هاتف جديد لجهة اتصال موجودة
     */
    public function addPhone(Request $request, PhoneContact $contact)
    {
        $currentUser = Auth::user();

        if (!$currentUser || (!$currentUser->isAdmin() && !$currentUser->isSupplier())) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإجراء هذه العملية.',
            ], 403);
        }

        $request->validate([
            'phone_number' => 'required|string|max:20',
        ]);

        try {
            $phoneNumber = PhoneNumber::create([
                'contact_id' => $contact->id,
                'phone_number' => $request->phone_number,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الرقم بنجاح.',
                'data' => [
                    'phone_number' => $phoneNumber,
                    'contact' => PhoneContact::with('phoneNumbers')->find($contact->id)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الرقم: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف رقم هاتف
     */
    public function deletePhone(PhoneNumber $phoneNumber)
    {
        $currentUser = Auth::user();

        if (!$currentUser || (!$currentUser->isAdmin() && !$currentUser->isSupplier())) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإجراء هذه العملية.',
            ], 403);
        }

        try {
            $contactId = $phoneNumber->contact_id;
            $phoneNumber->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الرقم بنجاح.',
                'data' => [
                    'contact' => PhoneContact::with('phoneNumbers')->find($contactId)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الرقم: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف جهة اتصال بالكامل مع أرقامها
     */
    public function deleteContact(PhoneContact $contact)
    {
        $currentUser = Auth::user();

        if (!$currentUser || (!$currentUser->isAdmin() && !$currentUser->isSupplier())) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإجراء هذه العملية.',
            ], 403);
        }

        try {
            $contact->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الشخص بنجاح.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الشخص: ' . $e->getMessage(),
            ], 500);
        }
    }
}
