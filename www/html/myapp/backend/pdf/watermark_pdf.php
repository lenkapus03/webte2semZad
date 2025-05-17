<?php
// Disable PHP error display in output but log them
ini_set('display_errors', 1); // Set to 0 in production
error_reporting(E_ALL);

// Set max execution time to handle larger files
ini_set('max_execution_time', 300); // 5 minutes

// Set memory limit for larger files
ini_set('memory_limit', '256M');

// Log basic information for debugging
error_log("Watermark PDF Script Called: " . __FILE__);
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
        $response['debug'][] = "POST data: " . json_encode($_POST);
    }

    // Check for POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    // Check for uploaded file
    if (empty($_FILES['file'])) {
        throw new Exception('No file uploaded', 400);
    }

    // Check for watermark text
    if (empty($_POST['watermark_text'])) {
        throw new Exception('Watermark text is required', 400);
    }

    // Use system temp directory for both uploads and results
    $sessionId = session_id();
    $uploadDir = sys_get_temp_dir() . '/pdf_watermark_uploads_' . $sessionId . '/';
    $resultDir = sys_get_temp_dir() . '/pdf_watermark_results_' . $sessionId . '/';

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
    $outputFilename = $safeOriginalFilename . '_watermarked_' . $resultId . '.pdf';
    $outputPath = $resultDir . $outputFilename;

    $response['debug'][] = "Output path: $outputPath";

    // Get watermark settings
    $watermarkText = $_POST['watermark_text'];
    $position = $_POST['position'] ?? 'center';
    $opacity = floatval($_POST['opacity'] ?? 0.5);
    $color = $_POST['color'] ?? '#000000';
    $fontSize = intval($_POST['font_size'] ?? 36);
    $rotation = intval($_POST['rotation'] ?? 45);

    // Create a Python script that uses pypdf to add watermark
    $pythonScriptPath = $uploadDir . 'watermark_pdf.py';
    $pythonScript = <<<EOT
#!/usr/bin/env python3
# PDF Watermarker using pypdf

import sys
import os
from io import BytesIO

# Import pypdf
from pypdf import PdfWriter, PdfReader
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import letter
from reportlab.lib.colors import HexColor
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

def create_watermark(text, position, opacity, color, font_size, page_width, page_height, rotation):
    # Create a temporary buffer for the watermark
    packet = BytesIO()
    
    # Create a PDF with the watermark text
    c = canvas.Canvas(packet, pagesize=(page_width, page_height))
    
    # Set font
    c.setFont("Helvetica", font_size)
    
    # Set color and opacity
    r, g, b = HexColor(color).rgb()
    c.setFillColorRGB(r, g, b, alpha=opacity)
    c.setStrokeColorRGB(r, g, b, alpha=opacity)
    
    # Calculate text width and height
    text_width = c.stringWidth(text, "Helvetica", font_size)
    text_height = font_size
    
    # Calculate position based on input
    if position == "center":
        x = (page_width - text_width) / 2
        y = (page_height - text_height) / 2
    elif position == "top-left":
        x = 50
        y = page_height - 50 - text_height
    elif position == "top-right":
        x = page_width - 50 - text_width
        y = page_height - 50 - text_height
    elif position == "bottom-left":
        x = 50
        y = 50
    elif position == "bottom-right":
        x = page_width - 50 - text_width
        y = 50
    else:  # default to center
        x = (page_width - text_width) / 2
        y = (page_height - text_height) / 2
    
    # Draw the text with custom rotation
    c.saveState()
    c.translate(x, y)
    c.rotate(float(rotation))
    c.drawString(0, 0, text)
    c.restoreState()
    
    c.save()
    packet.seek(0)
    return packet

def add_watermark(input_path, output_path, text, position, opacity, color, font_size, rotation):
    # Check that input file exists
    if not os.path.exists(input_path):
        print(f"Error: Input file does not exist: {input_path}")
        return False
    
    try:
        # Open the input PDF
        reader = PdfReader(input_path)
        writer = PdfWriter()
        
        # Get first page dimensions
        first_page = reader.pages[0]
        page_width = float(first_page.mediabox[2])
        page_height = float(first_page.mediabox[3])
        
        # Create watermark for each page
        for page in reader.pages:
            watermark_packet = create_watermark(text, position, opacity, color, font_size, page_width, page_height, rotation)
            watermark_reader = PdfReader(watermark_packet)
            watermark_page = watermark_reader.pages[0]
            page.merge_page(watermark_page)
            writer.add_page(page)
        
        # Write output
        with open(output_path, 'wb') as output_file:
            writer.write(output_file)
        
        print(f"Successfully added watermark to PDF: {output_path}")
        return True
    except Exception as e:
        print(f"Error processing PDF: {str(e)}")
        return False

if __name__ == "__main__":
    if len(sys.argv) != 9:
        print("Error: Incorrect number of arguments.")
        print("Usage: watermark_pdf.py input_file output_path watermark_text position opacity color font_size rotation")
        exit(1)
    
    input_file = sys.argv[1]
    output_path = sys.argv[2]
    watermark_text = sys.argv[3]
    position = sys.argv[4]
    opacity = float(sys.argv[5])
    color = sys.argv[6]
    font_size = int(sys.argv[7])
    rotation = float(sys.argv[8])
    
    print(f"Input file: {input_file}")
    print(f"Output path: {output_path}")
    print(f"Watermark text: {watermark_text}")
    print(f"Position: {position}")
    print(f"Opacity: {opacity}")
    print(f"Color: {color}")
    print(f"Font size: {font_size}")
    print(f"Rotation: {rotation}°")
    
    if add_watermark(input_file, output_path, watermark_text, position, opacity, color, font_size, rotation):
        print("Successfully added watermark")
        exit(0)
    else:
        print("Watermark addition failed")
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
        escapeshellarg($outputPath) . " " .
        escapeshellarg($watermarkText) . " " .
        escapeshellarg($position) . " " .
        escapeshellarg($opacity) . " " .
        escapeshellarg($color) . " " .
        escapeshellarg($fontSize) . " " .
        escapeshellarg($rotation) . " 2>&1";

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
        throw new Exception("Watermarked PDF was not created", 500);
    }

    // Verify the file size
    if (filesize($outputPath) === 0) {
        throw new Exception("Watermarked PDF is empty", 500);
    }

    // Store the session variables needed for download
    $_SESSION['pdf_file'] = $outputPath;
    $_SESSION['pdf_id'] = $resultId;
    $_SESSION['pdf_original_filename'] = $safeOriginalFilename . '_watermarked';

    // Set successful response
    $response = [
        'success' => true,
        'result_id' => $resultId,
        'message' => 'Watermark added to PDF successfully',
        'debug' => $response['debug']
    ];

    // Clean up uploaded file and temporary Python script
    if (file_exists($filePath)) {
        @unlink($filePath);
    }

    if (file_exists($pythonScriptPath)) {
        @unlink($pythonScriptPath);
    }

    // Add a cleanup function to remove old files
    // This could be enhanced to run periodically through a cron job
    function cleanupOldFiles($directory, $maxAge = 3600) {
        if (is_dir($directory)) {
            $files = glob($directory . '*');
            $now = time();

            foreach ($files as $file) {
                if (is_file($file) && ($now - filemtime($file) > $maxAge)) {
                    @unlink($file);
                }
            }
        }
    }

    // Clean up files older than 1 hour
    cleanupOldFiles($uploadDir);
    cleanupOldFiles($resultDir);

} catch (Exception $e) {
    // Make sure we're sending the HTTP status code
    $statusCode = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($statusCode);

    // Set error response
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    $response['debug'][] = "Error: " . $e->getMessage();

    error_log('PDF Watermark Error: ' . $e->getMessage());
} catch (Throwable $t) {
    http_response_code(500);

    // Set error response for unexpected errors
    $response['success'] = false;
    $response['error'] = 'An unexpected error occurred';
    $response['debug'][] = "Throwable: " . $t->getMessage();

    error_log('PDF Watermark Critical Error: ' . $t->getMessage());
}

// Send the JSON response
echo json_encode($response);
exit;