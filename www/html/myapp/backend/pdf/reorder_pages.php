<?php
// Backend script for reordering PDF pages
// Filename: reorder_pages.php

// Disable PHP error display in output but log them
ini_set('display_errors', 1); // Set to 0 in production
error_reporting(E_ALL);

// Set max execution time to handle larger files
ini_set('max_execution_time', 300); // 5 minutes

// Set memory limit for larger files
ini_set('memory_limit', '256M');

// Log basic information for debugging
error_log("Reorder PDF Pages Script Called: " . __FILE__);
error_log("Current Directory: " . __DIR__);
error_log("Document Root: " . $_SERVER['DOCUMENT_ROOT']);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);

// Make sure we send a JSON response
header('Content-Type: application/json');
session_start();

// Initialize response array
$response = [
    'success' => false,
    'error' => null,
    'result_id' => null,
    'message' => null,
    'debug' => []  // For debugging information
];

// Function to check if a file is a PDF
function isPDF($file) {
    global $response;
    // Simple check based on MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $response['debug'][] = "File MIME type: " . $mime;
        return $mime === 'application/pdf';
    }

    // Fallback to extension check
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $response['debug'][] = "File extension: " . $extension;
    return $extension === 'pdf';
}

try {
    // For now, let's allow usage without authentication for testing
    $testMode = true;

    // Add request data to debug info
    $response['debug'][] = "Request method: " . $_SERVER['REQUEST_METHOD'];
    $response['debug'][] = "Files in request: " . (empty($_FILES) ? "No" : "Yes");
    if (!empty($_FILES)) {
        $response['debug'][] = "Files structure: " . json_encode($_FILES);
    }
    if (!empty($_POST)) {
        // Filter out sensitive data from the debug info
        $debugPost = $_POST;
        $response['debug'][] = "POST data: " . json_encode($debugPost);
    }

    // Check for POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    // Check for uploaded file
    if (empty($_FILES['file'])) {
        throw new Exception('No file uploaded', 400);
    }

    // Check for page order
    if (empty($_POST['page_order'])) {
        throw new Exception('No page order specified', 400);
    }

    // Parse page order
    $pageOrder = json_decode($_POST['page_order'], true);
    if (!is_array($pageOrder)) {
        throw new Exception("Invalid page order data provided", 400);
    }

    // Use system temp directory instead of the web directory for both uploads and results
    // This avoids permission issues
    $uploadDir = sys_get_temp_dir() . '/pdf_reorder_uploads_' . session_id() . '/';
    $resultDir = sys_get_temp_dir() . '/pdf_reorder_results_' . session_id() . '/';

    // Create directories with error checking
    foreach ([$uploadDir, $resultDir] as $dir) {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new Exception('Failed to create directory: ' . $dir, 500);
            }
        }

        // Make sure directories are writable
        if (!is_writable($dir)) {
            throw new Exception("Directory is not writable: $dir", 500);
        }
    }

    $response['debug'][] = "Upload directory: $uploadDir";
    $response['debug'][] = "Results directory: $resultDir";

    // Process uploaded file
    $file = $_FILES['file'];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds the upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds the MAX_FILE_SIZE directive in the form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];

        $errorMessage = isset($errorMessages[$file['error']])
            ? $errorMessages[$file['error']]
            : 'Unknown upload error';

        throw new Exception("Error uploading {$file['name']}: $errorMessage", 400);
    }

    // Check if the file is a PDF
    if (!isPDF($file)) {
        throw new Exception("File is not a PDF: {$file['name']}", 400);
    }

    // Generate a secure filename
    $originalFilename = pathinfo($file['name'], PATHINFO_FILENAME);
    $safeOriginalFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
    $filename = md5(uniqid() . $file['name']) . '.pdf';
    $filePath = $uploadDir . $filename;

    // Move the file with error checking
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception("Failed to save uploaded file: {$file['name']}", 500);
    }

    $response['debug'][] = "File processed: {$file['name']} -> $filePath";

    // Generate a unique ID for the result
    $resultId = uniqid();
    $outputPath = $resultDir . $resultId . '.pdf';

    $response['debug'][] = "Output path: $outputPath";
    $response['debug'][] = "Reordering pages to: " . implode(", ", $pageOrder);

    // Create Python script to reorder pages
    $pythonScriptPath = $uploadDir . 'reorder_pages.py';
    $pythonScript = <<<EOT
#!/usr/bin/env python3
# PDF Page Reorderer using pypdf

import sys
import os
import json
from pypdf import PdfReader, PdfWriter

def reorder_pdf_pages(input_path, output_path, page_order_json):
    # Check that input file exists
    if not os.path.exists(input_path):
        print(f"Error: Input file does not exist: {input_path}")
        return False
    
    try:
        # Parse page order
        page_order = json.loads(page_order_json)
        
        # Open the PDF file
        reader = PdfReader(input_path)
        writer = PdfWriter()
        page_count = len(reader.pages)
        
        print(f"Processing PDF: {input_path} with {page_count} pages")
        print(f"New page order: {', '.join(map(str, page_order))}")
        
        # Validate page order
        if len(page_order) != page_count:
            print(f"Error: Page order length ({len(page_order)}) does not match PDF page count ({page_count})")
            return False
            
        for page_num in page_order:
            if page_num < 1 or page_num > page_count:
                print(f"Error: Invalid page number {page_num} in page order. Must be between 1 and {page_count}")
                return False
        
        # Add pages in the new order
        for i, page_num in enumerate(page_order):
            # page_num is 1-based, but pypdf is 0-based
            writer.add_page(reader.pages[page_num - 1])
            print(f"Adding page {page_num} at position {i+1}")
        
        # Write the output file
        with open(output_path, 'wb') as output_file:
            writer.write(output_file)
        
        print(f"Output written to: {output_path}")
        print(f"REORDERED:success")
        return True
    except Exception as e:
        print(f"Error processing PDF: {str(e)}")
        return False

if __name__ == "__main__":
    # Get arguments from command line
    if len(sys.argv) != 4:
        print("Error: Incorrect number of arguments.")
        print("Usage: reorder_pages.py input_file output_path page_order_json")
        exit(1)
    
    input_file = sys.argv[1]
    output_path = sys.argv[2]
    page_order_json = sys.argv[3]
    
    print(f"Input file: {input_file}")
    print(f"Output path: {output_path}")
    print(f"Page order JSON: {page_order_json}")
    
    if reorder_pdf_pages(input_file, output_path, page_order_json):
        print("PDF page reordering successful")
        exit(0)
    else:
        print("PDF page reordering failed")
        exit(1)
EOT;

    // Write the Python script to a file
    if (file_put_contents($pythonScriptPath, $pythonScript) === false) {
        throw new Exception("Failed to create Python script", 500);
    }

    // Make the script executable
    chmod($pythonScriptPath, 0755);

    // Prepare the command to run the Python script with system Python
    $pageOrderJson = json_encode($pageOrder);
    $command = "/usr/bin/python3 " .
        escapeshellarg($pythonScriptPath) . " " .
        escapeshellarg($filePath) . " " .
        escapeshellarg($outputPath) . " " .
        escapeshellarg($pageOrderJson) . " 2>&1";

    $response['debug'][] = "Executing command: " . $command;

    // Execute the command
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);

    $response['debug'][] = "Command output: " . implode("\n", $output);
    $response['debug'][] = "Return code: " . $returnCode;

    // Check if the command was successful
    if ($returnCode !== 0) {
        throw new Exception("Python script failed: " . implode("\n", $output), 500);
    }

    // Verify the output file exists
    if (!file_exists($outputPath)) {
        throw new Exception("Output file was not created", 500);
    }

    // Verify the output file size
    if (filesize($outputPath) === 0) {
        throw new Exception("Output file is empty", 500);
    }

    // For universal download system
    $_SESSION['pdf_file'] = $outputPath;
    $_SESSION['pdf_id'] = $resultId;
    $_SESSION['pdf_original_filename'] = $safeOriginalFilename;

    // Set successful response
    $response = [
        'success' => true,
        'result_id' => $resultId,
        'message' => 'PDF pages successfully reordered',
        'debug' => $response['debug']
    ];

    // Clean up uploaded file and temporary Python script
    if (file_exists($filePath)) {
        @unlink($filePath);
    }

    if (file_exists($pythonScriptPath)) {
        @unlink($pythonScriptPath);
    }

} catch (Exception $e) {
    // Make sure we're sending the HTTP status code
    $statusCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($statusCode);

    // Set error response
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    $response['debug'][] = "Error: " . $e->getMessage();

    error_log('PDF Reorder Pages Error: ' . $e->getMessage());
} catch (Throwable $t) {
    http_response_code(500);

    // Set error response for unexpected errors
    $response['success'] = false;
    $response['error'] = 'An unexpected error occurred';
    $response['debug'][] = "Throwable: " . $t->getMessage();

    error_log('PDF Reorder Pages Critical Error: ' . $t->getMessage());
}

// Send the JSON response
echo json_encode($response);
exit;