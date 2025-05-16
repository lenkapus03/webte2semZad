<?php

session_start();
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/utilities.php';
require_once __DIR__ . '/../auth/api_key.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$username = $_SESSION['username'];

// Get the API key
$apiKey = getUserApiKey($username);

if ($apiKey!=null) {
    echo json_encode(['success' => true, 'api_key' => $apiKey]);
} else {
    echo json_encode(['success' => false, 'error' => 'No API key found']);
}