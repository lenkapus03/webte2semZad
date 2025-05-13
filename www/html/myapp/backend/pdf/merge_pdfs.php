<?php
// Disable PHP error display in output but log them
ini_set('display_errors', 1); // Set to 0 in production
error_reporting(E_ALL);

// Set max execution time to handle larger files
ini_set('max_execution_time', 300); // 5 minutes

// Set memory limit for larger files
ini_set('memory_limit', '256M');

// Log basic information for debugging
error_log("Merge PDF Script Called: " . __FILE__);
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

    // Check for POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    // Check for uploaded files
    if (empty($_FILES['files'])) {
        throw new Exception('No files uploaded', 400);
    }

    // Use system temp directory instead of the web directory for both uploads and results
    // This avoids permission issues
    $uploadDir = sys_get_temp_dir() . '/pdf_uploads_' . session_id() . '/';
    $resultDir = sys_get_temp_dir() . '/pdf_results_' . session_id() . '/';

    // Create upload directory with error checking
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory: ' . $uploadDir, 500);
        }
    }

    // Create results directory with error checking
    if (!is_dir($resultDir)) {
        if (!@mkdir($resultDir, 0755, true)) {
            throw new Exception('Failed to create results directory: ' . $resultDir, 500);
        }
    }

    // Make sure directories are writable
    if (!is_writable($uploadDir)) {
        throw new Exception("Upload directory is not writable: $uploadDir", 500);
    }
    if (!is_writable($resultDir)) {
        throw new Exception("Results directory is not writable: $resultDir", 500);
    }

    $response['debug'][] = "Upload directory: $uploadDir";
    $response['debug'][] = "Results directory: $resultDir";

    $filePaths = [];
    $uploadedFiles = [];

    // Process uploaded files
    if (is_array($_FILES['files']['name'])) {
        $fileCount = count($_FILES['files']['name']);

        $response['debug'][] = "Number of files: $fileCount";

        if ($fileCount < 2) {
            throw new Exception('At least two PDF files are required for merging', 400);
        }

        for ($i = 0; $i < $fileCount; $i++) {
            $file = [
                'name' => $_FILES['files']['name'][$i],
                'type' => $_FILES['files']['type'][$i],
                'tmp_name' => $_FILES['files']['tmp_name'][$i],
                'error' => $_FILES['files']['error'][$i],
                'size' => $_FILES['files']['size'][$i],
            ];

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
            $filename = md5(uniqid() . $file['name']) . '.pdf';
            $filePath = $uploadDir . $filename;

            // Move the file with error checking
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception("Failed to save uploaded file: {$file['name']}", 500);
            }

            $filePaths[] = $filePath;
            $uploadedFiles[] = $file['name'];

            $response['debug'][] = "File " . ($i + 1) . " processed: {$file['name']} -> $filePath";
        }
    } else {
        throw new Exception('Invalid file upload format', 400);
    }

    // Generate a unique ID for the result
    $resultId = uniqid();
    $outputPath = $resultDir . $resultId . '.pdf';

    $response['debug'][] = "Output path: $outputPath";

    // Create a Python script that uses the installed pypdf
    $pythonScriptPath = $uploadDir . 'merge_pdfs.py';
    $pythonScript = <<<EOT
#!/usr/bin/env python3
# PDF Merger using pypdf

import sys
import os

# Import pypdf
from pypdf import PdfWriter, PdfReader

def merge_pdfs(input_paths, output_path):
    # Check that all input files exist
    for path in input_paths:
        if not os.path.exists(path):
            print(f"Error: Input file does not exist: {path}")
            return False
    
    # Create PDF writer object
    writer = PdfWriter()
    
    # Add all pages from each PDF
    for path in input_paths:
        try:
            reader = PdfReader(path)
            page_count = len(reader.pages)
            print(f"Processing file: {path} with {page_count} pages")
            for page_num in range(page_count):
                writer.add_page(reader.pages[page_num])
        except Exception as e:
            print(f"Error reading PDF {path}: {str(e)}")
            return False
    
    # Write the merged PDF to output file
    try:
        with open(output_path, 'wb') as output_file:
            writer.write(output_file)
        print(f"Successfully wrote merged PDF to {output_path}")
        return True
    except Exception as e:
        print(f"Error writing output file: {str(e)}")
        return False

if __name__ == "__main__":
    # Get arguments from command line
    if len(sys.argv) < 3:
        print("Error: Not enough arguments. Need at least 2 input files and 1 output file.")
        exit(1)
    
    input_files = sys.argv[1:-1]  # All arguments except the last one
    output_file = sys.argv[-1]    # Last argument is the output file
    
    print(f"Input files: {input_files}")
    print(f"Output file: {output_file}")
    
    if merge_pdfs(input_files, output_file):
        print("PDF merge successful")
        exit(0)
    else:
        print("PDF merge failed")
        exit(1)
EOT;

    // Write the Python script to a file
    if (file_put_contents($pythonScriptPath, $pythonScript) === false) {
        throw new Exception("Failed to create Python script", 500);
    }

    // Make the script executable
    chmod($pythonScriptPath, 0755);

    // Prepare the command to run the Python script with system Python
    $command = "/usr/bin/python3 " . escapeshellarg($pythonScriptPath) . " ";

    // Add each file path as a separate argument
    foreach ($filePaths as $filePath) {
        $command .= escapeshellarg($filePath) . " ";
    }
    $command .= escapeshellarg($outputPath) . " 2>&1";

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

    // Verify all input files were actually used
    $inputFilesUsed = false;
    foreach ($output as $line) {
        if (strpos($line, "Processing file:") !== false) {
            $inputFilesUsed = true;
            break;
        }
    }

    if (!$inputFilesUsed) {
        throw new Exception("Python script did not process any input files", 500);
    }

    // Verify the output file exists
    if (!file_exists($outputPath)) {
        throw new Exception("Output file was not created", 500);
    }

    // Verify the output file size
    if (filesize($outputPath) === 0) {
        throw new Exception("Output file is empty", 500);
    }

    // Store the session variables needed for download
    $_SESSION['pdf_merge_file'] = $outputPath;
    $_SESSION['pdf_merge_id'] = $resultId;

    // Set successful response
    $response = [
        'success' => true,
        'result_id' => $resultId,
        'message' => 'PDF files merged successfully',
        'file_count' => count($filePaths),
        'debug' => $response['debug']
    ];

    // Clean up uploaded files and temporary Python script
    foreach ($filePaths as $filePath) {
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    // Delete the Python script
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

    error_log('PDF Merge Error: ' . $e->getMessage());
} catch (Throwable $t) {
    http_response_code(500);

    // Set error response for unexpected errors
    $response['success'] = false;
    $response['error'] = 'An unexpected error occurred';
    $response['debug'][] = "Throwable: " . $t->getMessage();

    error_log('PDF Merge Critical Error: ' . $t->getMessage());
}

// Send the JSON response
echo json_encode($response);
exit;