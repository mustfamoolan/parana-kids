<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhoneContact;
use App\Models\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhoneBookController extends Controller
{
    /**
     * Display the phone book page.
     */
    public function index(Request $request)
    {
        // التأكد من أن المستخدم مدير أو مجهز
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

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

        // عدد النتائج في الصفحة (مع الحفاظ على القيمة عند التنقل)
        $perPage = $request->input('per_page', 20);
        $contacts = $query->orderBy('name')->paginate($perPage)->appends($request->except('page'));

        return view('admin.phone-book.index', compact('contacts'));
    }

    /**
     * Store a new contact.
     */
    public function store(Request $request)
    {
        // التأكد من أن المستخدم مدير أو مجهز
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
        ]);

        $contact = PhoneContact::create([
            'name' => $request->name,
        ]);

        PhoneNumber::create([
            'contact_id' => $contact->id,
            'phone_number' => $request->phone_number,
        ]);

        return redirect()->route('admin.phone-book.index')
            ->with('success', 'تم إضافة الشخص بنجاح.');
    }

    /**
     * Add a new phone number to an existing contact.
     */
    public function addPhone(Request $request, PhoneContact $contact)
    {
        // التأكد من أن المستخدم مدير أو مجهز
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        $request->validate([
            'phone_number' => 'required|string|max:20',
        ]);

        PhoneNumber::create([
            'contact_id' => $contact->id,
            'phone_number' => $request->phone_number,
        ]);

        return redirect()->route('admin.phone-book.index')
            ->with('success', 'تم إضافة الرقم بنجاح.');
    }

    /**
     * Delete a phone number.
     */
    public function deletePhone(PhoneNumber $phoneNumber)
    {
        // التأكد من أن المستخدم مدير أو مجهز
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        $phoneNumber->delete();

        return redirect()->route('admin.phone-book.index')
            ->with('success', 'تم حذف الرقم بنجاح.');
    }

    /**
     * Delete a contact and all its phone numbers.
     */
    public function deleteContact(PhoneContact $contact)
    {
        // التأكد من أن المستخدم مدير أو مجهز
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        $contact->delete();

        return redirect()->route('admin.phone-book.index')
            ->with('success', 'تم حذف الشخص بنجاح.');
    }
}
