<?php
// Set a clear error handler.
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    die("<div style='padding:20px; background-color:#f8d7da; color:#721c24; border:1px solid #f5c6cb;'>
            <h1>An Error Occurred</h1><p><strong>Error:</strong> $errstr</p><p><strong>File:</strong> $errfile on line <strong>$errline</strong></p>
        </div>");
});

session_start();
require_once '../../src/includes/db_connect.php';

// Security check
if (($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Unauthorized access.");
}

// --- Get Form Data Safely ---
$member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
$product_ids = $_POST['product_ids'] ?? [];
$quantities = $_POST['quantities'] ?? [];
$payment_method = $_POST['payment_method'] ?? '';
$admin_id = $_SESSION['user_id'];
$total_points = 0; $total_price = 0; $line_items = [];
$total_unilevel_bonus = 0; // NEW: Variable for the total unilevel bonus

// --- 1. Validation and Calculation ---
if (empty($member_id) || empty($product_ids)) { die("Member ID and at least one product are required."); }

$stmt_mem = $mysqli->prepare("SELECT id FROM users WHERE id = ? AND role = 'member'");
$stmt_mem->bind_param("i", $member_id);
$stmt_mem->execute();
if ($stmt_mem->get_result()->num_rows === 0) { die("Member with ID $member_id not found."); }
$stmt_mem->close();

foreach ($product_ids as $index => $product_id) {
    if (empty($product_id) || empty($quantities[$index])) continue;
    $quantity = (int)$quantities[$index];
    // --- CHANGE: Fetch unilevel_bonus ---
    $stmt_prod = $mysqli->prepare("SELECT member_price, points_value, unilevel_bonus, stock_quantity FROM products WHERE id = ?");
    $stmt_prod->bind_param("i", $product_id);
    $stmt_prod->execute();
    $prod_res = $stmt_prod->get_result();
    if ($prod = $prod_res->fetch_assoc()) {
        if ($prod['stock_quantity'] < $quantity) {
            throw new Exception("Insufficient stock for a product in the cart.");
        }
        $total_price += $prod['member_price'] * $quantity;
        $total_points += $prod['points_value'] * $quantity;
        // --- NEW: Calculate Unilevel Bonus for this line item ---
        $total_unilevel_bonus += $prod['unilevel_bonus'] * $quantity;
        $line_items[] = ['id' => $product_id, 'qty' => $quantity, 'price' => $prod['member_price'], 'points' => $prod['points_value']];
    }
    $stmt_prod->close();
}
// --- Your existing check for total_points > 0 is correct ---
if ($total_points <= 0 && $total_unilevel_bonus <= 0) { die("Invalid cart contents. Please ensure products are selected."); }


// --- 2. Start Transaction ---
$mysqli->begin_transaction();
try {
    // A. Handle Payment - Your existing logic is correct
    if ($payment_method === 'E-Wallet') {
        // ... (E-Wallet deduction code)
    }

    // B. Log the main sale record - Your existing logic is correct
    $stmt_sale = $mysqli->prepare("INSERT INTO product_sales (member_id, total_amount, total_points, payment_method, processed_by_id) VALUES (?, ?, ?, ?, ?)");
    $stmt_sale->bind_param("idisi", $member_id, $total_price, $total_points, $payment_method, $admin_id);
    $stmt_sale->execute();
    $sale_id = $mysqli->insert_id;
    if ($sale_id <= 0) throw new Exception("Failed to log sale.");
    $stmt_sale->close();

    // C. Log line items and deduct stock - Your existing logic is correct
    $stmt_item = $mysqli->prepare("INSERT INTO product_sale_items (sale_id, product_id, quantity, price_per_item, points_per_item) VALUES (?, ?, ?, ?, ?)");
    $stmt_stock = $mysqli->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
    foreach($line_items as $item) {
        // ... (logging and stock deduction logic)
    }
    $stmt_item->close();
    $stmt_stock->close();

    // D. Award Binary Points up the genealogy tree - Your existing logic is correct
    if ($total_points > 0) {
        $stmt_tree = $mysqli->prepare("SELECT upline_id, position FROM genealogy_tree WHERE user_id = ?");
        $stmt_tree->bind_param("i", $member_id);
        $stmt_tree->execute();
        if ($tree_info = $stmt_tree->get_result()->fetch_assoc()) {
            // ... (Your existing while loop to award binary points)
        }
        $stmt_tree->close();
    }
    // --- NEW: F. Credit Redundant Binary Points to the Purchaser ---
if ($total_points > 0) {
    $stmt_redundant = $mysqli->prepare("
        UPDATE member_profiles 
        SET redundant_points_balance = redundant_points_balance + ? 
        WHERE user_id = ?
    ");
    $stmt_redundant->bind_param("ii", $total_points, $member_id);
    $stmt_redundant->execute();
    $stmt_redundant->close();
}
// --- END OF NEW LOGIC ---
    
    // --- E: NEW - UNILEVEL BONUS PAYOUT LOGIC ---
    if ($total_unilevel_bonus > 0) {
        $upline_sponsors = [];
        $current_user_for_upline_search = $member_id; // Start from the purchaser
        $safety_counter = 0;

        // Find up to 10 levels of sponsors
        while (count($upline_sponsors) < 10 && $current_user_for_upline_search > 0 && $safety_counter < 20) {
            $stmt_upline = $mysqli->prepare("SELECT sponsor_id FROM genealogy_tree WHERE user_id = ?");
            $stmt_upline->bind_param("i", $current_user_for_upline_search);
            $stmt_upline->execute();
            $upline_result = $stmt_upline->get_result();

            if ($upline_row = $upline_result->fetch_assoc()) {
                $sponsor_id = (int)$upline_row['sponsor_id'];
                // A person cannot be their own sponsor, and the sponsor must exist.
                if ($sponsor_id > 0 && $sponsor_id != $current_user_for_upline_search) {
                    $upline_sponsors[] = $sponsor_id; // Add sponsor to the list to be paid
                    $current_user_for_upline_search = $sponsor_id; // The next person to search from is this sponsor
                } else { break; } // Reached the top or a self-sponsorship, stop.
            } else { break; } // User not in tree, stop.
            $stmt_upline->close();
            $safety_counter++;
        }
        
        // Pay the full bonus amount to each upline sponsor found
        if (!empty($upline_sponsors)) {
            $stmt_unilevel_comm = $mysqli->prepare("INSERT INTO commissions (user_id, type, amount, source_user_id, cycle_id) VALUES (?, 'unilevel_bonus', ?, ?, ?)");
            $stmt_unilevel_wallet = $mysqli->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + ?");
            $cycle_id = 'UNILEVEL-' . date('Y-m-d'); // Use a distinct cycle ID for this type of bonus

            foreach ($upline_sponsors as $upline_id) {
                // Record commission
                $stmt_unilevel_comm->bind_param("idis", $upline_id, $total_unilevel_bonus, $member_id, $cycle_id);
                $stmt_unilevel_comm->execute();
                
                // Update wallet
                $stmt_unilevel_wallet->bind_param("idd", $upline_id, $total_unilevel_bonus, $total_unilevel_bonus);
                $stmt_unilevel_wallet->execute();
            }
            $stmt_unilevel_comm->close();
            $stmt_unilevel_wallet->close();
        }
    }
    // --- END OF UNILEVEL LOGIC ---
    
    $mysqli->commit();
    header('Location: generate_receipt.php?sale_id=' . $sale_id);
    exit();

} catch (Exception $e) {
    $mysqli->rollback();
    die("<div style='padding:20px; background-color:#f8d7da; color:#721c24;'><h1>Transaction Failed</h1><p>Reason: " . $e->getMessage() . "</p></div>");
}

$mysqli->close();
?>