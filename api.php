<?php

header('Content-Type: application/json; charset=utf-8');

$num = trim($_GET['num'] ?? '');
$msg = trim($_GET['msg'] ?? '');

if ($num === '' || $msg === '') {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'error' => 'Missing parameters. Required: num, msg'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('curl_init') || !function_exists('mb_strlen')) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'error' => 'Required PHP extensions are missing: cURL or mbstring'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$msgLength = mb_strlen($msg, 'UTF-8');
$halfLength = (int) ceil($msgLength / 2);

$namePart = mb_substr($msg, 0, $halfLength, 'UTF-8');
$businessNamePart = mb_substr(
    $msg,
    $halfLength,
    $msgLength,
    'UTF-8'
);

$payload = [
    'name' => $namePart,
    'phone_number' => $num,
    'business_name' => $businessNamePart,
];

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => 'https://api-merchant.carrybee.com/api/v2/merchant/register',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ),
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
    ],
]);

try {
    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($response === false) {
        http_response_code(502);
        echo json_encode([
            'status' => false,
            'error' => 'cURL request failed: ' . $curlError
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(
        $httpCode >= 100 && $httpCode <= 599 ? $httpCode : 502
    );

    $decoded = json_decode($response, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode(
            $decoded,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    } else {
        echo json_encode([
            'http_code' => $httpCode,
            'raw_response' => $response
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'error' => 'Request processing failed'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} finally {
    curl_close($curl);
}