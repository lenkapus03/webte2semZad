<?php
header('Content-Type: application/json');
session_start();

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
                'endpoint' => 'PDF Merge API',
                'description' => 'API for merging multiple PDF files',
                'methods' => [
                    'GET' => 'Get information about this API endpoint',
                    'POST' => 'Upload PDF files to be merged'
                ],
                'post_parameters' => [
                    'files' => 'Array of PDF files to merge (min 2 files required)'
                ],
                'post_response' => [
                    'success' => 'Boolean indicating success',
                    'result_id' => 'ID of the merged PDF file for download',
                    'message' => 'Result message',
                    'file_count' => 'Number of merged files'
                ],
                'download_url' => '/myapp/backend/api/api_download_pdf.php?id={result_id}'
            ];
            break;

        case 'POST':
            // Generate a unique result ID here
            $resultId = uniqid();

            // Define the expected save path for merge_pdfs.php
            $outputPath = __DIR__ . '/../pdf/results/' . $resultId . '.pdf';

            // Pass the output path as a POST parameter to merge_pdfs.php
            $_POST['output_path'] = $outputPath;

            // Forward the request to the merge_pdfs.php script
            $_SERVER['HTTP_X_API_KEY'] = $apiKey; // Ensure API key is passed

            // Get the file from the current request
            if (empty($_FILES['files'])) {
                throw new Exception('No files uploaded', 400);
            }

            logUserAction($username, 'merge_pdf', getRequestSource());

            // Buffer the output of merge_pdfs.php
            ob_start();
            include __DIR__ . '/../pdf/merge_pdfs.php';
            $mergePdfOutput = ob_get_clean();

            // Decode the JSON response from merge_pdfs.php
            $mergePdfResult = json_decode($mergePdfOutput, true);

            if ($mergePdfResult && $mergePdfResult['success'] && isset($mergePdfResult['result_id'])) {
                // Store necessary information in the session for the download script
                if (!isset($_SESSION['pdf_id'])) {
                    $_SESSION['pdf_id'] = $mergePdfResult['result_id'];
                }

                // Keep original default name only if not already set
                if (!isset($_SESSION['pdf_original_filename'])) {
                    $_SESSION['pdf_original_filename'] = 'merged_document.pdf';
                }

                $response = [
                    'success' => true,
                    'result_id' => $mergePdfResult['result_id'],
                    'message' => $mergePdfResult['message'] ?? 'PDFs merged successfully',
                    'file_count' => $mergePdfResult['file_count'] ?? count($_FILES['files'])
                ];
            } else {
                // If merge_pdfs.php failed, return its error message
                $response = [
                    'success' => false,
                    'error' => $mergePdfResult['error'] ?? 'Failed to merge PDFs'
                ];
                http_response_code(500); // Or another appropriate error code
            }

            break;

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