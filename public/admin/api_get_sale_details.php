<?php
session_start();
header('Content-Type: application/json');
require_once '../../src/includes/db_connect.php';

if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    http_response_code(403);
    echo json_encode(['valid' => false, 'message' => 'Authentication failed.']);
    exit();
}

$response = ['valid' => false, 'message' => 'Invalid Sale ID format.'];
$sale_id_input = isset($_GET['sale_id']) ? trim($_GET['sale_id']) : '';
$sale_id = (int)preg_replace('/[^0-9]/', '', $sale_id_input);

if ($sale_id > 0) {
    // 1. Check if sale exists and is unused
    $stmt_sale = $mysqli->prepare("SELECT id FROM product_sales WHERE id = ? AND is_code_generated = 0");
    $stmt_sale->bind_param("i", $sale_id);
    $stmt_sale->execute();
    if ($stmt_sale->get_result()->num_rows !== 1) {
        $response['message'] = '✗ Invalid or already used Sale ID.';
        echo json_encode($response); exit();
    }
    $stmt_sale->close();

    // 2. Check for the "Paid Account" product and SUM its quantity
    $paid_account_barcode = '11110000';
    $stmt_items = $mysqli->prepare("
        SELECT SUM(psi.quantity) as total_quantity 
        FROM product_sale_items psi
        JOIN products p ON psi.product_id = p.id
        WHERE psi.sale_id = ? AND p.barcode = ?
    ");
    $stmt_items->bind_param("is", $sale_id, $paid_account_barcode);
    $stmt_items->execute();
    $item = $stmt_items->get_result()->fetch_assoc();
    $stmt_items->close();
    
    if ($item && $item['total_quantity'] > 0) {
        $response['valid'] = true;
        $response['heads'] = (int)$item['total_quantity'];
        $response['message'] = '✓ Sale ID is valid for ' . $item['total_quantity'] . ' head(s).';
    } else {
        $response['message'] = '✗ This Sale ID does not contain a Paid Account purchase.';
    }
}

echo json_encode($response);
$mysqli->close();
?>