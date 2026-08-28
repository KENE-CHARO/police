<?php

$base = 'http://127.0.0.1:8000';
$email = 'apitest' . time() . '@example.com';
$data = ['name' => 'API Test', 'email' => $email, 'password' => 'secret123', 'password_confirmation' => 'secret123'];

$ch = curl_init($base . '/api/auth/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$res = curl_exec($ch);
if ($res === false) {
    echo 'curl error: ' . curl_error($ch) . PHP_EOL;
    exit(1);
}

echo "Register response:\n" . $res . PHP_EOL;

$json = json_decode($res, true);
$token = $json['token'] ?? null;
if (! $token) {
    echo "No token returned\n";
    exit(1);
}

echo "Token: " . substr($token, 0, 24) . "..." . PHP_EOL;

$ch2 = curl_init($base . '/api/plaintes');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $token]);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(['titre' => 'Plainte via script', 'description' => 'test enchaîné']));
$res2 = curl_exec($ch2);
if ($res2 === false) {
    echo 'curl2 error: ' . curl_error($ch2) . PHP_EOL;
    exit(1);
}

echo "Create plainte response:\n" . $res2 . PHP_EOL;

return 0;
