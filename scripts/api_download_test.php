<?php
$base = 'http://127.0.0.1:8000';
$email = 'apitest' . time() . '@example.com';
$data = ['name' => 'API DL Test', 'email' => $email, 'password' => 'secret123', 'password_confirmation' => 'secret123'];

$ch = curl_init($base . '/api/auth/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$res = curl_exec($ch);
$json = json_decode($res, true);
$token = $json['token'] ?? null;

// create plainte
$ch2 = curl_init($base . '/api/plaintes');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $token]);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(['titre' => 'DL Test','description'=>'desc']));
$res2 = curl_exec($ch2);
$json2 = json_decode($res2, true);
$plainteId = $json2['id'] ?? null;

// upload file
$tmp = __DIR__ . '/tmp_upload_dl.txt';
file_put_contents($tmp, "dl test\n");
$ch3 = curl_init($base . '/api/plaintes/' . $plainteId . '/attachments');
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3, CURLOPT_POST, true);
$cfile = new CURLFile($tmp, mime_content_type($tmp), basename($tmp));
curl_setopt($ch3, CURLOPT_POSTFIELDS, ['file' => $cfile]);
curl_setopt($ch3, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$res3 = curl_exec($ch3);
$json3 = json_decode($res3, true);
$attachmentId = $json3['id'] ?? null;

// download
$ch4 = curl_init($base . '/api/plaintes/' . $plainteId . '/attachments/' . $attachmentId);
curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch4, CURLOPT_HEADER, true);
curl_setopt($ch4, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$res4 = curl_exec($ch4);
$info = curl_getinfo($ch4);

echo "HTTP_CODE: " . $info['http_code'] . PHP_EOL;
echo "HEADERS+BODY:\n" . $res4 . PHP_EOL;

@unlink($tmp);

return 0;
