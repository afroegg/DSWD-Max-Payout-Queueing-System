<?php
include_once(__DIR__ . '/../config/app.php');

function normalizePHNumber($number) {
    $number = preg_replace('/\D+/', '', $number);

    if (strpos($number, '63') === 0) {
        return $number;
    }

    if (strpos($number, '09') === 0) {
        return '63' . substr($number, 1);
    }

    if (strpos($number, '9') === 0 && strlen($number) === 10) {
        return '63' . $number;
    }

    return $number;
}

function sendSemaphoreSMS($number, $message) {
    $number = normalizePHNumber($number);

    $payload = [
        'apikey'  => SEMAPHORE_API_KEY,
        'number'  => $number,
        'message' => $message
    ];

    $ch = curl_init('https://api.semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        return [
            'success' => false,
            'status' => 'curl_error',
            'response' => $curlError
        ];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true,
            'status' => 'sent',
            'response' => $response
        ];
    }

    return [
        'success' => false,
        'status' => 'http_error_' . $httpCode,
        'response' => $response
    ];
}