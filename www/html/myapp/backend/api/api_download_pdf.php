<?php
// Disable PHP error display in output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Include required files
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../auth/utilities.php';
require_once __DIR__ . '/../auth/api_key.php';

// Handle preflight requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
    header('Access-Control-Max-Age: 86400');  // Cache preflight request for 24 hours
    exit;
}

// Set CORS headers for actual requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

try {
    // Check for API key
    if (!isset($_SERVER['HTTP_X_API_KEY']) && !isset($_GET['api_key'])) {
        throw new Exception('API key is required', 401);
    }

    // Get API key from header or query parameter
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';

    // Validate API key
    if (!validateApiKey($apiKey)) {
        throw new Exception('Invalid API key', 401);
    }

    // Get username from API key
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT username FROM users WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Invalid API key', 401);
    }

    $username = $user['username'];

    // Check if result ID is provided
    if (!isset($_GET['id'])) {
        throw new Exception('Result ID is required', 400);
    }

    $resultId = $_GET['id'];

    // Validate result ID format
    if (!preg_match('/^[a-z0-9]+$/i', $resultId)) {
        throw new Exception('Invalid result ID format', 400);
    }

    // Path to the merged PDF file
    $filePath = __DIR__ . '/../pdf/results/' . $resultId . '.pdf';

    // Check if the file exists
    if (!file_exists($filePath)) {
        throw new Exception('File not found', 404);
    }

    // Log the download action
    if (function_exists('logUserAction')) {
        try {
            logUserAction($username, 'api_download_pdf', 'api');
        } catch (Exception $e) {
            error_log("Failed to log download action: " . $e->getMessage());
            // Continue even if logging fails
        }
    }

    // Set headers for file download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="merged_document.pdf"');
    header('Content-Length: ' . filesize($filePath));

    // Output the file
    readfile($filePath);
    exit;

} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($statusCode);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);

    error_log('API PDF Download Error: ' . $e->getMessage());
} catch (Throwable $t) {
    http_response_code(500);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'An unexpected error occurred'
    ]);

    error_log('API PDF Download Critical Error: ' . $t->getMessage());
}