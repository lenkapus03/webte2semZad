<?php
// Disable PHP error display in output but log them
ini_set('display_errors', 1); // Set to 0 in production
error_reporting(E_ALL);

// Set max execution time to handle larger files
ini_set('max_execution_time', 300); // 5 minutes

// Set memory limit for larger files
ini_set('memory_limit', '256M');

// Log basic information for debugging
error_log("Unlock PDF Script Called: " . __FILE__);
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

    // Check for uploaded file
    if (empty($_FILES['file'])) {
        throw new Exception('No file uploaded', 400);
    }

    // Get password
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if (empty($password)) {
        throw new Exception('Password must be provided to decrypt the file', 400);
    }


    // Use system temp directory for both uploads and results
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

    // Create a Python script that uses the installed pypdf
    $pythonScriptPath = $uploadDir . 'decrypt_pdf.py';
    $pythonScript = <<<EOT
#!/usr/bin/env python3
from pypdf import PdfReader, PdfWriter
import sys
import os

if len(sys.argv) < 4:
    print("Usage: decrypt_pdf.py input.pdf output.pdf password")
    sys.exit(1)

input_file = sys.argv[1]
output_file = sys.argv[2]
password = sys.argv[3]

try:
    reader = PdfReader(input_file)
    if reader.is_encrypted:
        if not reader.decrypt(password):
            print("Error: Incorrect password")
            sys.exit(1)

    writer = PdfWriter()
    for page in reader.pages:
        writer.add_page(page)

    with open(output_file, "wb") as f:
        writer.write(f)

    print("PDF decryption successful")
    sys.exit(0)
except Exception as e:
    print(f"Decryption failed: {str(e)}")
    sys.exit(1)
EOT;

    // Write the Python script to a file
    if (file_put_contents($pythonScriptPath, $pythonScript) === false) {
        throw new Exception("Failed to create Python script", 500);
    }

    // Make the script executable
    chmod($pythonScriptPath, 0755);

    // Prepare the command to run the Python script
    $command = "/usr/bin/python3 " . escapeshellarg($pythonScriptPath) . " ";
    $command .= escapeshellarg($filePath) . " ";
    $command .= escapeshellarg($outputPath) . " ";
    $command .= escapeshellarg($password) . " ";
    $command .= escapeshellarg($safeOriginalFilename) . " 2>&1";

    $response['debug'][] = "Executing command: " . $command;

    // Execute the command
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);

    $response['debug'][] = "Command output: " . implode("\n", $output);
    $response['debug'][] = "Return code: " . $returnCode;


    if ($returnCode !== 0) {
        $outputMessage = implode("\n", $output);
        if (strpos($outputMessage, 'Incorrect password') !== false) {
            throw new Exception("Incorrect password", 422); // Unauthorized, alebo 400
        } else {
            throw new Exception("Python decryption failed: " . $outputMessage, 500);
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

    // Store the session variables needed for download
    $_SESSION['pdf_file'] = $outputPath;
    $_SESSION['pdf_id'] = $resultId;
    $_SESSION['pdf_original_filename'] = $safeOriginalFilename;

    // Set successful response
    $response = [
        'success' => true,
        'result_id' => $resultId,
        'message' => 'PDF password successfully removed',
        'debug' => $response['debug']
    ];

    // Clean up uploaded file and temporary Python script
    if (file_exists($filePath)) {
        @unlink($filePath);
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

    error_log('PDF Decryption Error: ' . $e->getMessage());
} catch (Throwable $t) {
    http_response_code(500);

    // Set error response for unexpected errors
    $response['success'] = false;
    $response['error'] = 'An unexpected error occurred';
    $response['debug'][] = "Throwable: " . $t->getMessage();

    error_log('PDF Decryption Critical Error: ' . $t->getMessage());
}

// Send the JSON response
echo json_encode($response);
exit;