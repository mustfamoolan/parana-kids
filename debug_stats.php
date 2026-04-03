<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $stats = App\Models\User::select('role', Illuminate\Support\Facades\DB::raw('count(*) as count'))
        ->groupBy('role')
        ->get();
    echo "\nUSER ROLE STATS:\n";
    print_r($stats->toArray());

    $fcmStats = App\Models\FcmToken::select('app_type', Illuminate\Support\Facades\DB::raw('count(*) as count'))
        ->groupBy('app_type')
        ->get();
    echo "\nFCM TOKEN STATS:\n";
    print_r($fcmStats->toArray());
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
