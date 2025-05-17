<?php
header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');

// Include required files
require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../auth/utilities.php';
require_once __DIR__ . '/../auth/api_key.php';

// Define allowed methods for the endpoint
$allowed_methods = ['POST', 'GET', 'OPTIONS'];

// Handle preflight requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: ' . implode(', ', $allowed_methods));
    header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
    header('Access-Control-Max-Age: 86400');
    exit;
}

// Set CORS headers for actual requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Initialize response
$response = ['success' => false];

function getRequestSource() {
    return $_SERVER['HTTP_X_REQUEST_SOURCE'] ?? 'backend';
}

try {
    // Check if method is allowed
    if (!in_array($_SERVER['REQUEST_METHOD'], $allowed_methods)) {
        throw new Exception('Method not allowed', 405);
    }

    // Check for API key
    if (!isset($_SERVER['HTTP_X_API_KEY'])) {
        throw new Exception('API key is required', 401);
    }

    $apiKey = $_SERVER['HTTP_X_API_KEY'];

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

    // Handle different request methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // API documentation response
            $response = [
                'success' => true,
                'endpoint' => 'PDF Comparison API',
                'description' => 'API for comparing two PDF files',
                'methods' => [
                    'GET' => 'Get information about this API endpoint',
                    'POST' => 'Upload two PDF files to compare'
                ],
                'post_parameters' => [
                    'file1' => 'First PDF file to compare',
                    'file2' => 'Second PDF file to compare',
                    'compare_metadata' => 'Boolean (1/0) to compare metadata (default: 1)',
                    'compare_images' => 'Boolean (1/0) to compare images (default: 1)',
                    'detailed_results' => 'Boolean (1/0) to get detailed page-by-page results (default: 1)'
                ],
                'post_response' => [
                    'success' => 'Boolean indicating success',
                    'identical' => 'Boolean indicating if files are identical',
                    'file1_pages' => 'Number of pages in first file',
                    'file2_pages' => 'Number of pages in second file',
                    'matching_pages' => 'Number of matching pages',
                    'metadata_match' => 'Boolean indicating if metadata matches',
                    'image_comparison' => 'Results of image comparison when enabled',
                    'detailed_results' => 'Array of page-by-page comparison results',
                    'download_url' => 'URL to download comparison report'
                ]
            ];
            break;

        case 'POST':
            // Forward the request to the compare_pdfs.php script
            $_SERVER['HTTP_X_API_KEY'] = $apiKey; // Ensure API key is passed

            // Check for uploaded files
            if (empty($_FILES['file1']) || empty($_FILES['file2'])) {
                throw new Exception('Two PDF files are required for comparison', 400);
            }

            logUserAction($username, 'compare_pdfs', getRequestSource());
            include __DIR__ . '/../pdf/compare_pdfs.php';
            exit;


        default:
            throw new Exception('Method not allowed', 405);
    }
} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($statusCode);
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    error_log('PDF Comparison API Error: ' . $e->getMessage());
} catch (Throwable $t) {
    http_response_code(500);
    $response = [
        'success' => false,
        'error' => 'An unexpected error occurred'
    ];
    error_log('PDF Comparison API Error: ' . $t->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);