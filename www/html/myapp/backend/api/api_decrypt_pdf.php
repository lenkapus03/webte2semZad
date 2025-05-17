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

try {
    // Check if method is allowed
    if (!in_array($_SERVER['REQUEST_METHOD'], $allowed_methods)) {
        throw new Exception('Method not allowed', 405);
    }

    // ✅ Kontrola API kľúča v hlavičke
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
    if (!$apiKey || !validateApiKey($apiKey)) {
        throw new Exception('Invalid or missing API key', 401);
    }

    // ✅ Získaj username podľa API kľúča
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT username FROM users WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Invalid API key (user not found)', 401);
    }

    $username = $user['username'];


    // Validate API key
    if (!validateApiKey($apiKey)) {
        throw new Exception('Invalid API key', 401);
    }

    // Handle different request methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get information about the API endpoint
            $response = [
                'success' => true,
                'endpoint' => 'PDF Decryption API',
                'description' => 'API for removing password from encrypted PDF files',
                'methods' => [
                    'GET' => 'Get information about this API endpoint',
                    'POST' => 'Upload a password-protected PDF file and provide password to decrypt it'
                ],
                'post_parameters' => [
                    'file' => 'Encrypted PDF file',
                    'password' => 'Password used to unlock the PDF file (required)'
                ],
                'post_response' => [
                    'success' => 'Boolean indicating success',
                    'result_id' => 'ID of the decrypted PDF file for download',
                    'message' => 'Result message'
                ],
                'download_url' => '/myapp/backend/api/api_download_pdf.php?id={result_id}'
            ];
            break;

        case 'POST':
            // Forward the request to the encrypt_pdf.php script
            // We'll do this by including the script and letting it handle the response
            //$_SERVER['HTTP_X_API_KEY'] = $apiKey; // Ensure API key is passed

            // Get the file from the current request
            if (empty($_FILES['file'])) {
                throw new Exception('No file uploaded', 400);
            }

            // Include the encrypt_pdf.php script
            include __DIR__ . '/../pdf/decrypt_pdf.php';
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
    error_log('PDF API Error: ' . $t->getMessage());
}

echo json_encode($response);