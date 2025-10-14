<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Place session_start() at the very top of the script.
session_start();

// Set a secure error handler.
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Log the detailed error for developers, NOT for the user.
    error_log("PHP Error: [$errno] $errstr in $errfile on line $errline");

    // Set a generic, user-friendly error message.
    // Ensure a session is active before trying to use it.
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['message'] = "A critical error occurred. Please contact support.";
        $_SESSION['msg_type'] = "danger";
    }

    // Attempt to redirect if possible.
    if (!headers_sent()) {
        header('Location: purchase_record.php');
    }
    exit(); // Always exit.
});

require_once '../../src/includes/db_connect.php';

// Authorization Check: Ensure session variables are set before use.
if (!isset($_SESSION['role'], $_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: purchase_record.php');
    exit();
}

// --- Input Sanitization & Validation ---
$package_id = isset($_POST['package_id']) ? (int)$_POST['package_id'] : 0;
$account_type = isset($_POST['account_type']) ? trim($_POST['account_type']) : '';
$num_heads = isset($_POST['num_heads']) ? (int)$_POST['num_heads'] : 0;
$sale_id_input = isset($_POST['sale_id']) ? trim($_POST['sale_id']) : '';
$admin_id = (int)$_SESSION['user_id'];
$source_sale_id = null; // Use null for clarity

// Simplified and clearer validation logic.
if ($package_id <= 0 || !in_array($num_heads, [1, 3, 7]) || empty($account_type)) {
    $_SESSION['message'] = "Invalid input. Please ensure all fields are correctly selected.";
    $_SESSION['msg_type'] = "danger";
    header('Location: purchase_record.php');
    exit();
}

// Fetch package details early to fail fast if the package is invalid.
$stmt_pkg = $mysqli->prepare("SELECT price, points_value FROM packages WHERE id = ?");
$stmt_pkg->bind_param("i", $package_id);
$stmt_pkg->execute();
$result_pkg = $stmt_pkg->get_result();
if ($result_pkg->num_rows !== 1) {
    $_SESSION['message'] = "Invalid package selected.";
    $_SESSION['msg_type'] = "danger";
    header('Location: purchase_record.php');
    exit();
}
$package = $result_pkg->fetch_assoc();
$stmt_pkg->close();


$mysqli->begin_transaction();
$generated_code_ids = [];

try {
    // --- 'Paid Account' specific logic, now inside the transaction to handle race conditions ---
    if ($account_type === 'Paid Account') {
        if (empty($sale_id_input)) {
            throw new Exception("A POS Sale ID is required for Paid Accounts.");
        }

        $source_sale_id = (int)preg_replace('/[^0-9]/', '', $sale_id_input);
        if ($source_sale_id <= 0) {
            throw new Exception("Invalid Sale ID format.");
        }

        // VULNERABILITY FIX: Use SELECT ... FOR UPDATE to lock the row and prevent race conditions.
        $stmt_sale = $mysqli->prepare("SELECT id FROM product_sales WHERE id = ? AND is_code_generated = 0 FOR UPDATE");
        $stmt_sale->bind_param("i", $source_sale_id);
        $stmt_sale->execute();
        if ($stmt_sale->get_result()->num_rows !== 1) {
            throw new Exception("Invalid, already used, or locked Sale ID (#$source_sale_id).");
        }
        $stmt_sale->close();

        // Check if the number of heads matches the quantity in the sale
        $paid_account_barcode = '11110000';
        $stmt_items = $mysqli->prepare("SELECT SUM(psi.quantity) as total_quantity FROM product_sale_items psi JOIN products p ON psi.product_id = p.id WHERE psi.sale_id = ? AND p.barcode = ?");
        $stmt_items->bind_param("is", $source_sale_id, $paid_account_barcode);
        $stmt_items->execute();
        $item = $stmt_items->get_result()->fetch_assoc();
        $stmt_items->close();

        if (!$item || (int)$item['total_quantity'] === 0) {
            throw new Exception("This Sale ID does not contain any Paid Account Package purchases.");
        }
        if ((int)$item['total_quantity'] !== $num_heads) {
            throw new Exception("The number of heads selected ($num_heads) does not match the quantity in Sale ID #$source_sale_id (which has " . $item['total_quantity'] . " heads).");
        }

        // Mark the sale as used
        $stmt_mark = $mysqli->prepare("UPDATE product_sales SET is_code_generated = 1 WHERE id = ?");
        $stmt_mark->bind_param("i", $source_sale_id);
        $stmt_mark->execute();
        $stmt_mark->close();
    }

    // --- Code Generation Loop ---
    $price_paid = ($account_type === 'FS Account') ? 0.00 : $package['price'];
    $points_earned = $package['points_value'];
    
    $stmt_code = $mysqli->prepare("INSERT INTO activation_codes (code, package_id, account_type, generated_by_id, source_sale_id) VALUES (?, ?, ?, ?, ?)");
    $stmt_hist = $mysqli->prepare("INSERT INTO purchase_history (package_id, account_type, activation_code_id, price_paid, points_earned, payment_method, processed_by_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

    for ($i = 0; $i < $num_heads; $i++) {
        $code = 'RMG-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt_code->bind_param("sisis", $code, $package_id, $account_type, $admin_id, $source_sale_id);
        $stmt_code->execute();
        $activation_code_id = $mysqli->insert_id;
        if ($activation_code_id <= 0) { throw new Exception("Code generation failed at database level."); }
        $generated_code_ids[] = $activation_code_id;

        $payment_method = 'Admin Generated';
        $stmt_hist->bind_param("isidisi", $package_id, $account_type, $activation_code_id, $price_paid, $points_earned, $payment_method, $admin_id);
        $stmt_hist->execute();
    }
    $stmt_code->close();
    $stmt_hist->close();

    $mysqli->commit();

    $_SESSION['generated_codes'] = $generated_code_ids;
    header('Location: codes_generated.php');
    exit();

} catch (Exception $e) {
    $mysqli->rollback();
    $_SESSION['message'] = "Error: " . $e->getMessage(); // Display the safe exception message
    $_SESSION['msg_type'] = "danger";
    header('Location: purchase_record.php');
    exit();
} finally {
    // It's good practice to close the connection, but often PHP handles this at script end.
    // If you have persistent connections, this is more important.
    $mysqli->close();
}
?>