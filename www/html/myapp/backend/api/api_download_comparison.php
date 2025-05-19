<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../auth/api_key.php';
require_once __DIR__ . '/../auth/utilities.php';

// Debug mode - enable for troubleshooting
$debug = false;

// Define allowed methods
$allowed_methods = ['GET', 'OPTIONS'];

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: ' . implode(', ', $allowed_methods));
    header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
    header('Access-Control-Max-Age: 86400');
    exit(0);
}

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

function getRequestSource() {
    return $_SERVER['HTTP_X_REQUEST_SOURCE'] ?? 'backend';
}

function debug_log($message, $data = null) {
    global $debug;
    if ($debug) {
        error_log("API_DOWNLOAD_DEBUG: $message");
        if ($data !== null) {
            error_log("API_DOWNLOAD_DEBUG_DATA: " . print_r($data, true));
        }
    }
}

try {
    // Method validation
    if (!in_array($_SERVER['REQUEST_METHOD'], $allowed_methods)) {
        throw new Exception('Method not allowed', 405);
    }

    // API key validation
    if (!isset($_SERVER['HTTP_X_API_KEY']) || !validateApiKey($_SERVER['HTTP_X_API_KEY'])) {
        throw new Exception('Valid API key is required', 401);
    }

    // Get authenticated user
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT username FROM users WHERE api_key = ?");
    $stmt->execute([$_SERVER['HTTP_X_API_KEY']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Invalid API key', 401);
    }

    $username = $user['username'];
    debug_log("Authenticated user", $username);

    // Validate report ID
    if (!isset($_GET['id']) || !preg_match('/^[a-f0-9]+$/', trim($_GET['id']))) {
        throw new Exception('Valid comparison ID is required', 400);
    }
    $resultId = trim($_GET['id']);
    debug_log("Result ID", $resultId);

    // Check both possible directory structures
    // Path format used in api_compare_pdfs.php
    $resultBase = sys_get_temp_dir() . '/pdf_comparison_results/';
    $resultDir = $resultBase . md5($username) . '/';
    $resultPath = $resultDir . $resultId . '.json';

    // Alternative path format
    $altResultDir = sys_get_temp_dir() . '/pdf_comparison_results_' . md5($username) . '/';
    $altResultPath = $altResultDir . $resultId . '.json';

    debug_log("Trying paths", ['primary' => $resultPath, 'alternative' => $altResultPath]);

    // Check if either file exists
    if (file_exists($resultPath)) {
        debug_log("Found result at primary path");
    } elseif (file_exists($altResultPath)) {
        debug_log("Found result at alternative path");
        $resultPath = $altResultPath;
    } else {
        // If debug mode is on, list all files in both directories to help diagnose
        if ($debug) {
            if (is_dir($resultDir)) {
                debug_log("Contents of primary directory", scandir($resultDir));
            } else {
                debug_log("Primary directory doesn't exist");
            }

            if (is_dir($altResultDir)) {
                debug_log("Contents of alternative directory", scandir($altResultDir));
            } else {
                debug_log("Alternative directory doesn't exist");
            }
        }

        throw new Exception('Comparison result not found or expired', 404);
    }

    // Validate JSON data
    $resultData = json_decode(file_get_contents($resultPath), true);
    if (json_last_error() !== JSON_ERROR_NONE || !$resultData) {
        debug_log("Invalid JSON data");
        throw new Exception('Invalid comparison data format', 500);
    }

    debug_log("Successfully loaded result data");

    $tempDir = sys_get_temp_dir() . '/mpdf_temp/';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    // Generate PDF report
    require_once '/var/www/html/vendor/autoload.php';
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'tempDir' => $tempDir,
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 15,
        'margin_bottom' => 15,
        'margin_header' => 5,
        'margin_footer' => 5,
        'showWatermarkText' => true,
        'watermarkTextAlpha' => 0.1,
        'watermarkText' => 'PDF Comparison Report'
    ]);

    // Build HTML content
    $html = '<style>
        body { font-family: Arial; font-size: 12px }
        h1 { color: #0066cc; font-size: 18px; border-bottom: 1px solid #0066cc }
        h2 { color: #0066cc; font-size: 16px }
        h3 { color: #0066cc; font-size: 14px }
        .file-info div { display: inline-block; width: 45%; vertical-align: top }
        table { width: 100%; border-collapse: collapse }
        th { background-color: #0066cc; color: white; text-align: left }
        th, td { padding: 5px; border: 1px solid #ddd }
        .diff-section { margin-bottom: 20px; page-break-inside: avoid }
        .diff-line { font-family: monospace; white-space: pre-wrap }
        .diff-added { background-color: #ddffdd; color: #006600 }
        .diff-removed { background-color: #ffdddd; color: #cc0000 }
        .diff-unchanged { color: #666666 }
        .page-header { font-weight: bold; margin-bottom: 5px }
        .diff-block { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px }
        .identical { color: green }
        .different { color: red }
    </style>
    <h1>PDF Comparison Report</h1>
    <div class="file-info">
        <div><h3>File 1</h3><p><strong>Name:</strong> ' . htmlspecialchars($resultData['file1_name'] ?? 'File 1') . '</p>
        <p><strong>Pages:</strong> ' . ($resultData['comparison']['file1_pages'] ?? 'N/A') . '</p></div>
        <div><h3>File 2</h3><p><strong>Name:</strong> ' . htmlspecialchars($resultData['file2_name'] ?? 'File 2') . '</p>
        <p><strong>Pages:</strong> ' . ($resultData['comparison']['file2_pages'] ?? 'N/A') . '</p></div>
    </div>';

    // Add comparison summary
    $html .= '<div class="summary"><h2>Summary</h2><p>The files are <span style="' .
        ($resultData['comparison']['identical'] ? 'color:green;font-weight:bold' : 'color:red;font-weight:bold') . '">' .
        ($resultData['comparison']['identical'] ? 'IDENTICAL' : 'DIFFERENT') . '</span></p>
             <table><tr><th>Comparison Aspect</th><th>Result</th></tr>
             <tr><td>Metadata Match</td><td>' . ($resultData['comparison']['metadata_match'] ? 'Yes' : 'No') . '</td></tr>
             <tr><td>Matching Pages</td><td>' . ($resultData['comparison']['matching_pages'] ?? 0) . ' of ' .
        ($resultData['comparison']['file1_pages'] ?? 'N/A') . '</td></tr></table></div>';

    // Add detailed page differences if needed
    if (!$resultData['comparison']['identical'] && !empty($resultData['comparison']['page_comparisons'])) {
        $html .= '<h2>Detailed Page Differences</h2>';

        foreach ($resultData['comparison']['page_comparisons'] as $index => $page) {
            $pageNumber = $index + 1;

            $html .= '<div class="diff-section">';
            $html .= '<div class="page-header">Page ' . $pageNumber . ' - ' .
                ($page['identical'] ? '<span class="identical">IDENTICAL</span>' : '<span class="different">DIFFERENT</span>') . '</div>';

            if (!$page['text_match']) {
                $html .= '<div class="diff-block">';
                $html .= '<h3>Text Differences</h3>';

                if (!empty($page['text_diff'])) {
                    $diffLines = explode("\n", $page['text_diff']);
                    foreach ($diffLines as $line) {
                        $firstChar = substr($line, 0, 1);
                        $content = htmlspecialchars(substr($line, 1));

                        if ($firstChar === '+') {
                            $html .= '<div class="diff-line diff-added">+' . $content . '</div>';
                        } elseif ($firstChar === '-') {
                            $html .= '<div class="diff-line diff-removed">-' . $content . '</div>';
                        } else {
                            $html .= '<div class="diff-line diff-unchanged">' . htmlspecialchars($line) . '</div>';
                        }
                    }
                } else {
                    $html .= '<div class="diff-line">Text content differs but no detailed diff available</div>';
                }

                $html .= '</div>'; // close diff-block
            }

            if (isset($page['images_match']) && !$page['images_match']) {
                $html .= '<div class="diff-block">';
                $html .= '<h3>Image Differences</h3>';
                $html .= '<div class="diff-line">';

                if (!empty($page['image_diff'])) {
                    $html .= htmlspecialchars($page['image_diff']);
                } else {
                    $html .= 'Images on this page differ';
                }

                $html .= '</div></div>'; // close diff-line and diff-block
            }

            $html .= '</div>'; // close diff-section
        }
    }

    // Add footer and generate PDF
    $html .= '<div style="text-align:center;font-size:10px;margin-top:20px">Report generated on: ' .
        date('Y-m-d H:i:s', $resultData['timestamp'] ?? time()) . '</div>';

    // Log user action
    logUserAction($username, 'download_comparison', getRequestSource());
    debug_log("Generating PDF");

    try {
        $mpdf->WriteHTML($html);

        // Output PDF with security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="comparison_report_' . $resultId . '.pdf"');
        $mpdf->Output('comparison_report_' . $resultId . '.pdf', 'D');
        exit(0);
    } catch (Exception $mpdfE) {
        debug_log("MPDF Error", $mpdfE->getMessage());
        throw new Exception('Failed to generate PDF report: ' . $mpdfE->getMessage(), 500);
    }

} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 && $e->getCode() <= 599 ? $e->getCode() : 500;
    http_response_code($statusCode);
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $statusCode
    ];

    if ($debug) {
        $response['debug'] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }


    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit(1);
}