<?php

use Illuminate\Support\Facades\Http;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Setting Telegram Bot Webhooks ---\n";

// Bot 1 (Original)
$token1 = config('services.telegram.bot_token');
$url1 = config('services.telegram.webhook_url');

if ($token1 && $url1) {
    echo "Bot 1 (Original): Setting webhook to: $url1...\n";
    try {
        $response1 = Http::post("https://api.telegram.org/bot{$token1}/setWebhook", [
            'url' => $url1
        ]);
        if ($response1->successful()) {
            echo "Bot 1 Webhook Status: SUCCESS! Response: " . json_encode($response1->json()) . "\n";
        } else {
            echo "Bot 1 Webhook Status: FAILED! Error: " . $response1->body() . "\n";
        }
    } catch (\Exception $e) {
        echo "Bot 1 Webhook Status: EXCEPTION! " . $e->getMessage() . "\n";
    }
} else {
    echo "Bot 1 (Original): Missing token or URL in configuration.\n";
}

echo "\n";

// Bot 2 (New)
$token2 = config('services.telegram_new.bot_token');
$url2 = config('services.telegram_new.webhook_url');

if ($token2 && $url2) {
    echo "Bot 2 (New): Setting webhook to: $url2...\n";
    try {
        $response2 = Http::post("https://api.telegram.org/bot{$token2}/setWebhook", [
            'url' => $url2
        ]);
        if ($response2->successful()) {
            echo "Bot 2 Webhook Status: SUCCESS! Response: " . json_encode($response2->json()) . "\n";
        } else {
            echo "Bot 2 Webhook Status: FAILED! Error: " . $response2->body() . "\n";
        }
    } catch (\Exception $e) {
        echo "Bot 2 Webhook Status: EXCEPTION! " . $e->getMessage() . "\n";
    }
} else {
    echo "Bot 2 (New): Missing token or URL in configuration.\n";
}

echo "--- Finished ---\n";
