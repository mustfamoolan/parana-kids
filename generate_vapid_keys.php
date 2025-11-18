<?php

require __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\VAPID;

echo "Generating VAPID Keys...\n\n";

$keys = VAPID::createVapidKeys();

echo "========================================\n";
echo "VAPID Keys Generated Successfully!\n";
echo "========================================\n\n";
echo "Public Key (FIREBASE_VAPID_KEY):\n";
echo $keys['publicKey'] . "\n\n";
echo "Private Key (VAPID_PRIVATE_KEY):\n";
echo $keys['privateKey'] . "\n\n";
echo "========================================\n";
echo "Add these to your .env file:\n";
echo "========================================\n";
echo "FIREBASE_VAPID_KEY=" . $keys['publicKey'] . "\n";
echo "VAPID_PRIVATE_KEY=" . $keys['privateKey'] . "\n";
echo "\n";

