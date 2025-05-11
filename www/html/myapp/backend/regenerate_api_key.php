<?php

require_once  "auth/api_key.php";
//require_once '/myapp/backend/auth/utilities.php';

header('Content-Type: application/json');

$response = ['success' => false, 'error' => null, 'api_key' => null];

try {
    session_start();

    if (empty($_SESSION['username'])) {
        throw new RuntimeException('Not authenticated', 401);
    }


    $newKey = assignApiKeyToUser($_SESSION['username']);
    if (!$newKey) {
        throw new RuntimeException('Failed to regenerate API key', 500);
    }

    logUserAction($_SESSION['username'], 'regenerate_api_key');

    $response = [
        'success' => true,
        'api_key' => $newKey
    ];

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    $response['error'] = $e->getMessage();
    error_log("API Key Error: " . $e->getMessage());
}

die(json_encode($response));
?>


