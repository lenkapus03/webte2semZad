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

// Enable detailed error logging for debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

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
                    'detailed_results' => 'Array of page-by-page comparison results',
                    'download_url' => 'URL to download comparison report'
                ],
                'example_usage' => [
                    'curl -X POST',
                    '-H "X-API-Key: your_api_key"',
                    '-F "file1=@document1.pdf"',
                    '-F "file2=@document2.pdf"',
                    '-F "compare_metadata=1"',
                    '-F "compare_images=1"',
                    'https://yourdomain.com/myapp/backend/api/compare_pdfs.php'
                ]
            ];
            break;

        case 'POST':
            // Check for uploaded files
            if (empty($_FILES['file1']) || empty($_FILES['file2'])) {
                throw new Exception('Two PDF files are required for comparison', 400);
            }

            // Create secure temporary directories
            $uploadBase = sys_get_temp_dir() . '/pdf_comparison_uploads/';
            $uploadDir = $uploadBase . md5($username . session_id()) . '/';

            // Create base directory if needed
            if (!is_dir($uploadBase)) {
                if (!@mkdir($uploadBase, 0777, true)) {
                    error_log("Failed to create directory: $uploadBase");
                    throw new Exception('Failed to create base upload directory', 500);
                }
                @chmod($uploadBase, 0777);
            }

            // Create user directory
            if (!is_dir($uploadDir)) {
                if (!@mkdir($uploadDir, 0777, true)) {
                    error_log("Failed to create directory: $uploadDir");
                    throw new Exception('Failed to create user upload directory', 500);
                }
                @chmod($uploadDir, 0777);
            }

            // Process uploaded files
            $file1 = $_FILES['file1'];
            $file2 = $_FILES['file2'];

            // Validate files
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            foreach ([$file1, $file2] as $index => $file) {
                if (!is_uploaded_file($file['tmp_name'])) {
                    throw new Exception('Invalid file upload for file' . ($index + 1), 400);
                }

                $mime = finfo_file($finfo, $file['tmp_name']);
                if ($mime !== 'application/pdf') {
                    throw new Exception('Uploaded file' . ($index + 1) . ' is not a PDF', 400);
                }

                // Check file size (10MB limit)
                if ($file['size'] > 10 * 1024 * 1024) {
                    throw new Exception('File' . ($index + 1) . ' size exceeds 10MB limit', 400);
                }
            }
            finfo_close($finfo);

            // Save files with secure names
            $file1Path = $uploadDir . md5(uniqid() . $file1['name']) . '.pdf';
            $file2Path = $uploadDir . md5(uniqid() . $file2['name']) . '.pdf';

            // Move uploaded files
            if (!move_uploaded_file($file1['tmp_name'], $file1Path)) {
                error_log("Failed to move file. Temp: " . $file1['tmp_name'] . " -> Dest: $file1Path");
                throw new Exception('Failed to save uploaded file 1', 500);
            }

            if (!move_uploaded_file($file2['tmp_name'], $file2Path)) {
                error_log("Failed to move file. Temp: " . $file2['tmp_name'] . " -> Dest: $file2Path");
                throw new Exception('Failed to save uploaded file 2', 500);
            }

            @chmod($file1Path, 0644);
            @chmod($file2Path, 0644);

            // Get comparison options from POST
            $compareMetadata = isset($_POST['compare_metadata']) ? (int)$_POST['compare_metadata'] : 1;
            $compareImages = isset($_POST['compare_images']) ? (int)$_POST['compare_images'] : 1;
            $detailedResults = isset($_POST['detailed_results']) ? (int)$_POST['detailed_results'] : 1;

            // Create Python script
            $pythonScript = <<<'EOT'
#!/usr/bin/env python3
import sys
import json
import difflib
from pypdf import PdfReader
from PIL import Image
from io import BytesIO
import hashlib
import unicodedata

def safe_text(text):
    if not text:
        return ""
    return unicodedata.normalize('NFC', text)

def get_image_hash(image_data):
    try:
        image = Image.open(BytesIO(image_data))
        image = image.convert("RGB")
        image = image.resize((64, 64), Image.LANCZOS)
        return hashlib.md5(image.tobytes()).hexdigest()
    except Exception:
        return None

def extract_images(page):
    images = []
    try:
        if '/Resources' in page and '/XObject' in page['/Resources']:
            xObjects = page['/Resources']['/XObject'].get_object()
            for obj in xObjects:
                xobj = xObjects[obj]
                if xobj['/Subtype'] == '/Image':
                    data = xobj.get_data()
                    images.append({
                        'data': data,
                        'hash': get_image_hash(data)
                    })
    except Exception:
        pass
    return images

def compare_pdfs(file1, file2, compare_metadata, compare_images):
    try:
        pdf1 = PdfReader(file1)
        pdf2 = PdfReader(file2)

        result = {
            'file1_pages': len(pdf1.pages),
            'file2_pages': len(pdf2.pages),
            'metadata_match': not compare_metadata or (str(pdf1.metadata) == str(pdf2.metadata)),
            'page_comparisons': [],
            'identical': True
        }

        min_pages = min(result['file1_pages'], result['file2_pages'])

        for i in range(min_pages):
            page1 = pdf1.pages[i]
            page2 = pdf2.pages[i]

            text1 = safe_text(page1.extract_text() or "")
            text2 = safe_text(page2.extract_text() or "")

            page_result = {
                'text_match': text1 == text2,
                'text_diff': ''.join(difflib.ndiff(
                    text1.splitlines(keepends=True),
                    text2.splitlines(keepends=True)
                )) if text1 != text2 else '',
                'identical': text1 == text2
            }

            if compare_images:
                images1 = extract_images(page1)
                images2 = extract_images(page2)
                hashes1 = [img['hash'] for img in images1 if img['hash']]
                hashes2 = [img['hash'] for img in images2 if img['hash']]
                page_result['images_match'] = hashes1 == hashes2
                page_result['identical'] = page_result['identical'] and page_result['images_match']

            result['page_comparisons'].append(page_result)
            if not page_result['identical']:
                result['identical'] = False

        result['matching_pages'] = sum(1 for p in result['page_comparisons'] if p['identical'])
        return result

    except Exception as e:
        return {'error': str(e)}

if __name__ == "__main__":
    if len(sys.argv) != 5:
        print("Usage: compare_pdfs.py file1 file2 compare_metadata compare_images")
        sys.exit(1)

    result = compare_pdfs(sys.argv[1], sys.argv[2], sys.argv[3] == '1', sys.argv[4] == '1')
    print(json.dumps(result))
    sys.exit(0 if 'error' not in result else 1)
EOT;

            $pythonScriptPath = $uploadDir . 'compare_pdfs.py';
            file_put_contents($pythonScriptPath, $pythonScript);
            chmod($pythonScriptPath, 0755);

            // Execute Python script with timeout
            $command = "timeout 60s /usr/bin/python3 " .
                escapeshellarg($pythonScriptPath) . " " .
                escapeshellarg($file1Path) . " " .
                escapeshellarg($file2Path) . " " .
                ($compareMetadata ? '1' : '0') . " " .
                ($compareImages ? '1' : '0') . " 2>&1";

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                error_log("Python script failed: " . implode("\n", $output));
                throw new Exception("PDF comparison failed: " . implode("\n", $output), 500);
            }

            $comparisonResults = json_decode(implode("\n", $output), true);
            if (!$comparisonResults || isset($comparisonResults['error'])) {
                error_log("Failed to parse comparison results: " . implode("\n", $output));
                throw new Exception($comparisonResults['error'] ?? "Failed to parse comparison results", 500);
            }

            // Store results for download
            $resultId = uniqid();
            $resultBase = sys_get_temp_dir() . '/pdf_comparison_results/';
            $resultDir = $resultBase . md5($username) . '/';

            // Create directories if needed
            if (!is_dir($resultBase)) {
                @mkdir($resultBase, 0777, true);
                @chmod($resultBase, 0777);
            }
            if (!is_dir($resultDir)) {
                @mkdir($resultDir, 0777, true);
                @chmod($resultDir, 0777);
            }

            // Save complete results including file names
            $resultData = [
                'timestamp' => time(),
                'file1_name' => $file1['name'],
                'file2_name' => $file2['name'],
                'comparison' => $comparisonResults
            ];

            $resultPath = $resultDir . $resultId . '.json';
            if (!file_put_contents($resultPath, json_encode($resultData))) {
                error_log("Failed to save comparison results: " . $resultPath);
                // Continue without download URL
            }

            // Prepare the API response
            $response = [
                'success' => true,
                'identical' => $comparisonResults['identical'] ?? false,
                'file1_pages' => $comparisonResults['file1_pages'] ?? 0,
                'file2_pages' => $comparisonResults['file2_pages'] ?? 0,
                'matching_pages' => $comparisonResults['matching_pages'] ?? 0,
                'metadata_match' => $comparisonResults['metadata_match'] ?? false,
                'detailed_results' => $detailedResults && isset($comparisonResults['page_comparisons']),
            ];

            if ($detailedResults && isset($comparisonResults['page_comparisons'])) {
                $response['page_comparisons'] = $comparisonResults['page_comparisons'];
            }

            if (isset($resultPath)) {
                $response['download_url'] = "api_download_comparison.php?id=" . $resultId;
            }

            // Log user action
            logUserAction($username, 'compare_pdfs', 'api');

            // Clean up
            $cleanup = function($path) {
                if (file_exists($path)) {
                    for ($i = 0; $i < 3; $i++) {
                        if (@unlink($path)) break;
                        usleep(100000);
                    }
                }
            };

            register_shutdown_function(function() use ($cleanup, $file1Path, $file2Path, $pythonScriptPath) {
                $cleanup($file1Path);
                $cleanup($file2Path);
                $cleanup($pythonScriptPath);
            });
            break;

        default:
            throw new Exception('Method not allowed', 405);
    }
} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 && $e->getCode() <= 599 ? $e->getCode() : 500;
    http_response_code($statusCode);
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $statusCode
    ];
    error_log('PDF Comparison API Error: ' . $e->getMessage());
} catch (Throwable $t) {
    http_response_code(500);
    $response = [
        'success' => false,
        'error' => 'An unexpected error occurred: ' . $t->getMessage()
    ];
    error_log('PDF Comparison API Error: ' . $t->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);