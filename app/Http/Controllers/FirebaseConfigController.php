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
            'apiKey' => env('FIREBASE_API_KEY', 'AIzaSyAXv3VHE9P1L5i71y4Z20nB-N4tLiA-TrU'),
            'authDomain' => env('FIREBASE_AUTH_DOMAIN', 'parana-kids.firebaseapp.com'),
            'projectId' => env('FIREBASE_PROJECT_ID', 'parana-kids'),
            'storageBucket' => env('FIREBASE_STORAGE_BUCKET', 'parana-kids.firebasestorage.app'),
            'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID', '130151352064'),
            'appId' => env('FIREBASE_APP_ID', '1:130151352064:web:42335c43d67f4ac49515e5'),
            'measurementId' => env('FIREBASE_MEASUREMENT_ID', 'G-HCTDLM0P9Y'),
            'vapidKey' => env('FIREBASE_VAPID_KEY', 'BET5Odck6WkOyun9SwgVCQjxpVcCi7o0WMCyu1vJbsX9K8kdNV-DGM-THOdKWBcXIYvo5rTH4E3cKX2LNmLGYX0'),
        ]);
    }
}

