<?php
$token = '8893948949:AAHZ2-ZRq8FoQ-aPzdTcYWrAqtCGyL_ee7c';
$url = 'https://parana-kids-main-sbv4op.laravel.cloud/delegate/login';
$text = '💼 دخول المندوب';

$data = [
    'menu_button' => [
        'type' => 'web_app',
        'text' => $text,
        'web_app' => [
            'url' => $url
        ]
    ]
];

$ch = curl_init("https://api.telegram.org/bot{$token}/setChatMenuButton");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));

$res = curl_exec($ch);
echo $res;
