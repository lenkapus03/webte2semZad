<?php
// Disable PHP error display in output but log them
ini_set('display_errors', 1); // Set to 0 in production
error_reporting(E_ALL);

// Set max execution time to handle larger files
ini_set('max_execution_time', 300); // 5 minutes

// Set memory limit for larger files
ini_set('memory_limit', '256M');

// Log basic information for debugging
error_log("Split PDF Script Called: " . __FILE__);
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
    'page_count' => 0,
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

    // Use system temp directory instead of the web directory for both uploads and results
    // This avoids permission issues
    $uploadDir = sys_get_temp_dir() . '/pdf_split_uploads_' . session_id() . '/';
    $resultDir = sys_get_temp_dir() . '/pdf_split_results_' . session_id() . '/';
    $zipDir = sys_get_temp_dir() . '/pdf_split_zips_' . session_id() . '/';

    // Create directories with error checking
    foreach ([$uploadDir, $resultDir, $zipDir] as $dir) {
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
    $response['debug'][] = "ZIP directory: $zipDir";

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
    $zipPath = $zipDir . $resultId . '.zip';

    $response['debug'][] = "ZIP output path: $zipPath";

    // Create a Python script that uses pypdf to split the PDF
    $pythonScriptPath = $uploadDir . 'split_pdf.py';
    $pythonScript = <<<EOT
#!/usr/bin/env python3
# PDF Splitter using pypdf

import sys
import os
import zipfile

# Import pypdf
from pypdf import PdfWriter, PdfReader

def split_pdf(input_path, output_dir, original_filename):
    # Check that input file exists
    if not os.path.exists(input_path):
        print(f"Error: Input file does not exist: {input_path}")
        return False
    
    try:
        # Open the PDF file
        reader = PdfReader(input_path)
        page_count = len(reader.pages)
        print(f"Processing PDF: {input_path} with {page_count} pages")
        
        # Create a list to store output files
        output_files = []
        
        # Extract each page as a separate PDF
        for page_num in range(page_count):
            writer = PdfWriter()
            writer.add_page(reader.pages[page_num])
            
            # Create output filename - use original name + page number
            output_filename = f"{original_filename}_page_{page_num + 1}.pdf"
            output_path = os.path.join(output_dir, output_filename)
            
            # Write the individual page to a PDF file
            with open(output_path, 'wb') as output_file:
                writer.write(output_file)
            
            output_files.append(output_path)
            print(f"Created page {page_num + 1}: {output_path}")
        
        return output_files, page_count
    except Exception as e:
        print(f"Error processing PDF: {str(e)}")
        return False

def create_zip(output_files, zip_path):
    try:
        with zipfile.ZipFile(zip_path, 'w') as zipf:
            for output_file in output_files:
                # Add file to the zip with just the filename (not the full path)
                zipf.write(output_file, os.path.basename(output_file))
                print(f"Added to ZIP: {output_file}")
        
        print(f"ZIP file created: {zip_path}")
        return True
    except Exception as e:
        print(f"Error creating ZIP: {str(e)}")
        return False

if __name__ == "__main__":
    # Get arguments from command line
    if len(sys.argv) != 5:
        print("Error: Incorrect number of arguments.")
        print("Usage: split_pdf.py input_file output_dir zip_path original_filename")
        exit(1)
    
    input_file = sys.argv[1]
    output_dir = sys.argv[2]
    zip_path = sys.argv[3]
    original_filename = sys.argv[4]
    
    print(f"Input file: {input_file}")
    print(f"Output directory: {output_dir}")
    print(f"ZIP path: {zip_path}")
    print(f"Original filename: {original_filename}")
    
    result = split_pdf(input_file, output_dir, original_filename)
    
    if result:
        output_files, page_count = result
        if create_zip(output_files, zip_path):
            print(f"Successfully split PDF into {page_count} pages and created ZIP")
            print(f"PAGE_COUNT:{page_count}")
            exit(0)
        else:
            print("ZIP creation failed")
            exit(1)
    else:
        print("PDF splitting failed")
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
        escapeshellarg($filePath) . " " .
        escapeshellarg($resultDir) . " " .
        escapeshellarg($zipPath) . " " .
        escapeshellarg($safeOriginalFilename) . " 2>&1";

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

    // Verify the ZIP file exists
    if (!file_exists($zipPath)) {
        throw new Exception("ZIP file was not created", 500);
    }

    // Verify the ZIP file size
    if (filesize($zipPath) === 0) {
        throw new Exception("ZIP file is empty", 500);
    }

    // Store the session variables needed for download
    $_SESSION['pdf_zip_file'] = $zipPath;
    $_SESSION['pdf_zip_id'] = $resultId;
    $_SESSION['pdf_zip_original_filename'] = $safeOriginalFilename;

    // Set successful response
    $response = [
        'success' => true,
        'result_id' => $resultId,
        'message' => 'PDF file split successfully',
        'page_count' => $pageCount,
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

    error_log('PDF Split Error: ' . $e->getMessage());
} catch (Throwable $t) {
    http_response_code(500);

    // Set error response for unexpected errors
    $response['success'] = false;
    $response['error'] = 'An unexpected error occurred';
    $response['debug'][] = "Throwable: " . $t->getMessage();

    error_log('PDF Split Critical Error: ' . $t->getMessage());
}

// Send the JSON response
echo json_encode($response);
exit;