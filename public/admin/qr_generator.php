<?php
// Force error reporting to see any issues
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Use the absolute path for the autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Use the core classes
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Get data from the URL
$data = $_GET['data'] ?? 'ERROR: No data provided';

try {
    // --- THIS IS THE FINAL, CORRECT SYNTAX FOR YOUR VERSION ---

    // 1. Create a new instance of the QrCode class with just the data
    $qrCode = new QrCode($data);

    // 2. Create a writer to generate the PNG image
    $writer = new PngWriter();

    // 3. Generate the result. The setSize() and setMargin() methods do not exist on QrCode.
    //    For basic sizing, the writer uses the defaults which are fine.
    //    To be explicit, you would create a builder, but this is the simplest way.
    $result = $writer->write($qrCode);

    // 4. Output the image directly to the browser
    header('Content-Type: '.$result->getMimeType());
    echo $result->getString();

} catch (Exception $e) {
    http_response_code(500);
    echo "Error generating QR Code: " . $e->getMessage();
}