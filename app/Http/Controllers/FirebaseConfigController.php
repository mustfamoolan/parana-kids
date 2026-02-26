<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FirebaseConfigController extends Controller
{
    /**
     * إرجاع Firebase config للـ frontend
     */
    public function getConfig()
    {
        return response()->json([
            'apiKey' => config('services.firebase.api_key', 'AIzaSyAXv3VHE9P1L5i71y4Z20nB-N4tLiA-TrU'),
            'authDomain' => config('services.firebase.auth_domain', 'parana-kids.firebaseapp.com'),
            'projectId' => config('services.firebase.project_id', 'parana-kids'),
            'storageBucket' => config('services.firebase.storage_bucket', 'parana-kids.firebasestorage.app'),
            'messagingSenderId' => config('services.firebase.messaging_sender_id', '130151352064'),
            'appId' => config('services.firebase.app_id', '1:130151352064:web:42335c43d67f4ac49515e5'),
            'measurementId' => config('services.firebase.measurement_id', 'G-HCTDLM0P9Y'),
            'vapidKey' => config('services.firebase.vapid_key', 'BET5Odck6WkOyun9SwgVCQjxpVcCi7o0WMCyu1vJbsX9K8kdNV-DGM-THOdKWBcXIYvo5rTH4E3cKX2LNmLGYX0'),
        ]);
    }
}

