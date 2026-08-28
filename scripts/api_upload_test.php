<?php
$base = 'http://127.0.0.1:8000';
$email = 'apitest' . time() . '@example.com';
$data = ['name' => 'API Upload Test', 'email' => $email, 'password' => 'secret123', 'password_confirmation' => 'secret123'];

$ch = curl_init($base . '/api/auth/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$res = curl_exec($ch);
if ($res === false) { echo 'curl error: ' . curl_error($ch) . PHP_EOL; exit(1); }
$json = json_decode($res, true);
$token = $json['token'] ?? null;
if (! $token) { echo "Register failed: $res\n"; exit(1); }

// create plainte
$ch2 = curl_init($base . '/api/plaintes');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $token]);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(['titre' => 'Plainte pour upload', 'description' => 'test upload']));
$res2 = curl_exec($ch2);
if ($res2 === false) { echo 'curl2 error: ' . curl_error($ch2) . PHP_EOL; exit(1); }
$json2 = json_decode($res2, true);
$plainteId = $json2['id'] ?? null;
if (! $plainteId) { echo "Create plainte failed: $res2\n"; exit(1); }

// write temp file
$tmp = __DIR__ . '/tmp_upload.txt';
file_put_contents($tmp, "fichier de test\n");

// upload file
$ch3 = curl_init($base . '/api/plaintes/' . $plainteId . '/attachments');
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3, CURLOPT_POST, true);
$cfile = new CURLFile($tmp, mime_content_type($tmp), basename($tmp));
curl_setopt($ch3, CURLOPT_POSTFIELDS, ['file' => $cfile]);
curl_setopt($ch3, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$res3 = curl_exec($ch3);
if ($res3 === false) { echo 'curl3 error: ' . curl_error($ch3) . PHP_EOL; exit(1); }

echo "Register response: $res\n";
echo "Create plainte response: $res2\n";
echo "Upload response: $res3\n";

// cleanup
@unlink($tmp);

return 0;
