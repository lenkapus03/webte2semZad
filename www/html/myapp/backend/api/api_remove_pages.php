<?php
header('Content-Type: application/json');

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
    header('Access-Control-Max-Age: 86400');  // Cache preflight request for 24 hours
    exit;
}

// Set CORS headers for actual requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle non-OPTIONS requests
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
            // Get information about the API endpoint
            $response = [
                'success' => true,
                'endpoint' => 'PDF Remove Pages API',
                'description' => 'API for removing pages from a PDF file',
                'methods' => [
                    'GET' => 'Get information about this API endpoint',
                    'POST' => 'Upload a PDF file to remove pages'
                ],
                'post_parameters' => [
                    'file' => 'Single PDF file to process',
                    'pages_to_remove' => 'JSON array of page numbers to remove (1-based)'
                ],
                'post_response' => [
                    'success' => 'Boolean indicating success',
                    'result_id' => 'ID of the modified PDF file for download',
                    'message' => 'Result message',
                    'removed_pages' => 'Number of pages removed',
                    'remaining_pages' => 'Number of pages remaining in the document'
                ],
                'download_url' => '/myapp/backend/api/api_download_pdf.php?id={result_id}'
            ];
            break;

        case 'POST':
            // Forward the request to the remove_pages.php script
            $_SERVER['HTTP_X_API_KEY'] = $apiKey; // Ensure API key is passed

            // Get the file from the current request
            if (empty($_FILES['file'])) {
                throw new Exception('No file uploaded', 400);
            }

            // Check for pages_to_remove parameter
            if (empty($_POST['pages_to_remove'])) {
                throw new Exception('No pages to remove specified', 400);
            }

            logUserAction($username, 'remove_pages_pdf', getRequestSource());

            // Include the remove_pages.php script
            include __DIR__ . '/../pdf/remove_pages.php';
            // The script will handle the response, so we exit here

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
} catch (Throwable $t) {
    http_response_code(500);
    $response = [
        'success' => false,
        'error' => 'An unexpected error occurred'
    ];
    error_log('PDF Remove Pages API Error: ' . $t->getMessage());
}

echo json_encode($response);