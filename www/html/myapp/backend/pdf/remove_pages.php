<?php
// Disable PHP error display in output but log them
ini_set('display_errors', 1); // Set to 0 in production
error_reporting(E_ALL);

// Set max execution time to handle larger files
ini_set('max_execution_time', 300); // 5 minutes

// Set memory limit for larger files
ini_set('memory_limit', '256M');

// Log basic information for debugging
error_log("Remove PDF Pages Script Called: " . __FILE__);
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
    'remaining_pages' => 0,
    'removed_pages' => 0,
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

    // Use system temp directory instead of the web directory for both uploads and results
    // This avoids permission issues
    $uploadDir = sys_get_temp_dir() . '/pdf_remove_uploads_' . session_id() . '/';
    $resultDir = sys_get_temp_dir() . '/pdf_remove_results_' . session_id() . '/';

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

    // Check if this is a page count request
    if (isset($_POST['action']) && $_POST['action'] === 'getPageCount') {
        // Create a Python script that uses pypdf to get the page count
        $pythonScriptPath = $uploadDir . 'get_page_count.py';
        $pythonScript = <<<EOT
#!/usr/bin/env python3
# PDF Page Counter using pypdf

import sys
from pypdf import PdfReader

if __name__ == "__main__":
    # Get arguments from command line
    if len(sys.argv) != 2:
        print("Error: Incorrect number of arguments.")
        print("Usage: get_page_count.py input_file")
        exit(1)
    
    input_file = sys.argv[1]
    
    try:
        # Open the PDF file
        reader = PdfReader(input_file)
        page_count = len(reader.pages)
        print(f"PAGE_COUNT:{page_count}")
        exit(0)
    except Exception as e:
        print(f"Error processing PDF: {str(e)}")
        exit(1)
EOT;

        // Write the Python script to a file
        if (file_put_contents($pythonScriptPath, $pythonScript) === false) {
            throw new Exception("Failed to create Python script", 500);
        }

        // Make the script executable
        chmod($pythonScriptPath, 0755);

        // Prepare the command to run the Python script with system Python
        $command = "/usr/bin/python3 " .
            escapeshellarg($pythonScriptPath) . " " .
            escapeshellarg($filePath) . " 2>&1";

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

        // Extract page count from output
        $pageCount = 0;
        foreach ($output as $line) {
            if (strpos($line, "PAGE_COUNT:") !== false) {
                $pageCount = intval(str_replace("PAGE_COUNT:", "", $line));
                break;
            }
        }

        // Return the page count
        $response = [
            'success' => true,
            'page_count' => $pageCount,
            'message' => 'Successfully retrieved page count',
            'debug' => $response['debug']
        ];

        // Clean up
        @unlink($pythonScriptPath);

        echo json_encode($response);
        exit;
    }

    // Get pages to remove
    if (!isset($_POST['pages_to_remove'])) {
        throw new Exception("No pages to remove specified", 400);
    }

    $pagesToRemove = json_decode($_POST['pages_to_remove'], true);

    if (!is_array($pagesToRemove)) {
        throw new Exception("Invalid pages to remove data provided", 400);
    }

    // Sort pages to remove in ascending order
    sort($pagesToRemove);

    $response['debug'][] = "Pages to remove: " . implode(", ", $pagesToRemove);

    // Generate a unique ID for the result
    $resultId = uniqid();
    $outputPath = $resultDir . $resultId . '.pdf';

    $response['debug'][] = "Output path: $outputPath";

    // Create Python script to remove pages
    $pythonScriptPath = $uploadDir . 'remove_pages.py';
    $pythonScript = <<<EOT
#!/usr/bin/env python3
# PDF Page Remover using pypdf

import sys
import os
import json
from pypdf import PdfReader, PdfWriter

def remove_pdf_pages(input_path, output_path, pages_to_remove_json):
    # Check that input file exists
    if not os.path.exists(input_path):
        print(f"Error: Input file does not exist: {input_path}")
        return False
    
    try:
        # Parse pages to remove
        pages_to_remove = json.loads(pages_to_remove_json)
        
        # Ensure pages_to_remove is sorted
        pages_to_remove.sort()
        
        # Open the PDF file
        reader = PdfReader(input_path)
        writer = PdfWriter()
        page_count = len(reader.pages)
        
        if len(pages_to_remove) >= page_count:
            print(f"Error: Cannot remove all pages. Pages to remove: {len(pages_to_remove)}, Total pages: {page_count}")
            return False
        
        print(f"Processing PDF: {input_path} with {page_count} pages")
        print(f"Removing pages: {', '.join(map(str, pages_to_remove))}")
        
        # Add all pages except those to be removed
        pages_kept = 0
        for i in range(page_count):
            page_num = i + 1  # 1-based page numbering
            if page_num not in pages_to_remove:
                writer.add_page(reader.pages[i])
                pages_kept += 1
                print(f"Keeping page {page_num}")
            else:
                print(f"Removing page {page_num}")
        
        # Write the output file
        with open(output_path, 'wb') as output_file:
            writer.write(output_file)
        
        print(f"Output written to: {output_path}")
        print(f"REMOVED_PAGES:{len(pages_to_remove)}")
        print(f"REMAINING_PAGES:{pages_kept}")
        return True
    except Exception as e:
        print(f"Error processing PDF: {str(e)}")
        return False

if __name__ == "__main__":
    # Get arguments from command line
    if len(sys.argv) != 4:
        print("Error: Incorrect number of arguments.")
        print("Usage: remove_pages.py input_file output_path pages_to_remove_json")
        exit(1)
    
    input_file = sys.argv[1]
    output_path = sys.argv[2]
    pages_to_remove_json = sys.argv[3]
    
    print(f"Input file: {input_file}")
    print(f"Output path: {output_path}")
    print(f"Pages to remove JSON: {pages_to_remove_json}")
    
    if remove_pdf_pages(input_file, output_path, pages_to_remove_json):
        print("PDF page removal successful")
        exit(0)
    else:
        print("PDF page removal failed")
        exit(1)
EOT;

    // Write the Python script to a file
    if (file_put_contents($pythonScriptPath, $pythonScript) === false) {
        throw new Exception("Failed to create Python script", 500);
    }

    // Make the script executable
    chmod($pythonScriptPath, 0755);

    // Prepare the command to run the Python script with system Python
    $pagesToRemoveJson = json_encode($pagesToRemove);
    $command = "/usr/bin/python3 " .
        escapeshellarg($pythonScriptPath) . " " .
        escapeshellarg($filePath) . " " .
        escapeshellarg($outputPath) . " " .
        escapeshellarg($pagesToRemoveJson) . " 2>&1";

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

    // Extract page removal info from output
    $removedPages = 0;
    $remainingPages = 0;
    foreach ($output as $line) {
        if (strpos($line, "REMOVED_PAGES:") !== false) {
            $removedPages = intval(str_replace("REMOVED_PAGES:", "", $line));
        } else if (strpos($line, "REMAINING_PAGES:") !== false) {
            $remainingPages = intval(str_replace("REMAINING_PAGES:", "", $line));
        }
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

    // Set successful response
    $response = [
        'success' => true,
        'result_id' => $resultId,
        'message' => 'PDF pages successfully removed',
        'removed_pages' => $removedPages,
        'remaining_pages' => $remainingPages,
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

    error_log('PDF Remove Pages Error: ' . $e->getMessage());
} catch (Throwable $t) {
    http_response_code(500);

    // Set error response for unexpected errors
    $response['success'] = false;
    $response['error'] = 'An unexpected error occurred';
    $response['debug'][] = "Throwable: " . $t->getMessage();

    error_log('PDF Remove Pages Critical Error: ' . $t->getMessage());
}

// Send the JSON response
echo json_encode($response);
exit;