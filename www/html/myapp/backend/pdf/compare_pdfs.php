<?php
header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');

// Disable PHP error display in output but log them
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set max execution time to handle larger files
ini_set('max_execution_time', 300);

// Set memory limit for larger files
ini_set('memory_limit', '256M');

// Make sure we send a JSON response
header('Content-Type: application/json');
session_start();

// Initialize response array
$response = [
    'success' => false,
    'error' => null,
    'identical' => false,
    'file1_pages' => 0,
    'file2_pages' => 0,
    'matching_pages' => 0,
    'metadata_match' => false,
    'debug' => []
];

// Function to check if a file is a PDF
function isPDF($file) {
    global $response;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $response['debug'][] = "File MIME type: " . $mime;
        return $mime === 'application/pdf';
    }
    return false;
}

try {
    // Check for POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    // Check for uploaded files
    if (empty($_FILES['file1']) || empty($_FILES['file2'])) {
        throw new Exception('Two PDF files are required for comparison', 400);
    }

    // Create secure temporary directory
    $uploadDir = sys_get_temp_dir() . '/pdf_compare_temp_' . session_id() . '/';
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0777, true)) {
            throw new Exception('Failed to create temp directory', 500);
        }
        @chmod($uploadDir, 0777);
    }

    // Process uploaded files
    $file1 = $_FILES['file1'];
    $file2 = $_FILES['file2'];

    // Validate files
    foreach ([$file1, $file2] as $file) {
        if (!isPDF($file)) {
            throw new Exception('Uploaded file is not a PDF', 400);
        }
        // Check file size (10MB limit)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('File size exceeds 10MB limit', 400);
        }
    }

    // Save files with secure names
    $file1Path = $uploadDir . 'file1_' . md5(uniqid()) . '.pdf';
    $file2Path = $uploadDir . 'file2_' . md5(uniqid()) . '.pdf';

    if (!move_uploaded_file($file1['tmp_name'], $file1Path) ||
        !move_uploaded_file($file2['tmp_name'], $file2Path)) {
        throw new Exception('Failed to save uploaded files', 500);
    }

    // Get comparison options
    $compareMetadata = isset($_POST['compare_metadata']) && $_POST['compare_metadata'] === '1';
    $compareImages = isset($_POST['compare_images']) && $_POST['compare_images'] === '1';
    $detailedResults = isset($_POST['detailed_results']) && $_POST['detailed_results'] === '1';

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
    $command = "timeout 30s /usr/bin/python3 " .
        escapeshellarg($pythonScriptPath) . " " .
        escapeshellarg($file1Path) . " " .
        escapeshellarg($file2Path) . " " .
        ($compareMetadata ? '1' : '0') . " " .
        ($compareImages ? '1' : '0') . " 2>&1";

    exec($command, $output, $returnCode);

    if ($returnCode !== 0) {
        throw new Exception("Python script failed: " . implode("\n", $output), 500);
    }

    $comparisonResults = json_decode(implode("\n", $output), true);
    if (!$comparisonResults || isset($comparisonResults['error'])) {
        throw new Exception($comparisonResults['error'] ?? "Failed to parse comparison results", 500);
    }

    // Build final response
    $response = [
        'success' => true,
        'identical' => $comparisonResults['identical'] ?? false,
        'file1_pages' => $comparisonResults['file1_pages'] ?? 0,
        'file2_pages' => $comparisonResults['file2_pages'] ?? 0,
        'matching_pages' => $comparisonResults['matching_pages'] ?? 0,
        'metadata_match' => $comparisonResults['metadata_match'] ?? false,
        'compare_images' => $compareImages,
        'detailed_results' => $detailedResults
    ];

    // Conditionally include detailed results
    if ($detailedResults && !empty($comparisonResults['page_comparisons'])) {
        $response['page_comparisons'] = $comparisonResults['page_comparisons'];
    }

    // Log user action
    if (isset($_SESSION['username'])) {
        require_once __DIR__ . '/../auth/utilities.php';
        logUserAction($_SESSION['username'], 'compare_pdfs');
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    http_response_code($e->getCode() ?: 500);
}

// Clean up
$cleanup = function($path) {
    if (file_exists($path)) {
        @unlink($path);
    }
};

$cleanup($file1Path ?? null);
$cleanup($file2Path ?? null);
$cleanup($pythonScriptPath ?? null);
@rmdir($uploadDir ?? null);

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);