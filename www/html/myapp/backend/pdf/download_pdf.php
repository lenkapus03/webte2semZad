<?php
// Enable error reporting for debugging (you can disable this in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

try {
    // Check if we have a PDF result ID and path in the session
    if (!isset($_SESSION['pdf_id']) || !isset($_SESSION['pdf_file'])) {
        throw new Exception('No PDF file found in session. Please merge files first.');
    }

    // Get the path from the session
    $filePath = $_SESSION['pdf_file'];
    $resultId = $_SESSION['pdf_id'];
    $originalFilename = $_SESSION['pdf_original_filename'] ?? 'pdf';

    // Check if the request ID matches the stored ID
    $requestId = isset($_GET['id']) ? $_GET['id'] : '';
    if (empty($requestId) || $requestId !== $resultId) {
        throw new Exception('Invalid result ID provided');
    }

    // Check if the file exists
    if (!file_exists($filePath)) {
        throw new Exception('File no longer exists - it may have expired');
    }

    // Check if the file is readable
    if (!is_readable($filePath)) {
        throw new Exception('File exists but is not readable: permission denied');
    }

    // Get the file size
    $fileSize = filesize($filePath);
    if ($fileSize === false) {
        throw new Exception('Cannot determine file size');
    }

    // Set headers for file download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $originalFilename . '.pdf"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');

    // Clear any output buffers to avoid corruption
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Output the file using readfile with error checking
    if (readfile($filePath) === false) {
        throw new Exception('Error reading file');
    }

    exit;

} catch (Exception $e) {
    // Set an appropriate HTTP status code
    $statusCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($statusCode);

    // Show user-friendly error page
    echo '<html><head><title>PDF Download Error</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 20px auto; padding: 20px; }
        h1 { color: #d9534f; }
        .error-box { background-color: #f2dede; border: 1px solid #ebccd1; color: #a94442; padding: 15px; border-radius: 4px; }
        .info-box { background-color: #d9edf7; border: 1px solid #bce8f1; color: #31708f; padding: 15px; border-radius: 4px; margin-top: 20px; }
        a { color: #337ab7; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>';
    echo '</head><body>';
    echo '<h1>PDF Download Error</h1>';

    echo '<div class="error-box">';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';

    echo '<div class="info-box">';
    echo '<p>Please try the following steps:</p>';
    echo '<ol>';
    echo '<li>Go back to the <a href="/myapp/frontend/merge_pdfs.html">PDF Merger page</a> and try again</li>';
    echo '<li>Make sure to download the PDF immediately after merging</li>';
    echo '<li>Try with smaller PDF files if the issue persists</li>';
    echo '</ol>';
    echo '</div>';

    echo '<p><a href="/myapp/index.php">Return to Dashboard</a></p>';
    echo '</body></html>';

    error_log('PDF Download Error: ' . $e->getMessage());
    exit;
}