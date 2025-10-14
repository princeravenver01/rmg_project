<?php
session_start();
require_once '../../src/includes/db_connect.php';

// Security
if ($_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: package_management.php');
    exit();
}

// Get form data
$package_id = (int)$_POST['id'];
$name = trim($_POST['name']);
$price = $_POST['price'];
$points_value = (int)$_POST['points_value'];
$product_ids = $_POST['product_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];

if ($package_id <= 0) {
    exit('Invalid Package ID.');
}

// --- Start Database Transaction ---
$mysqli->begin_transaction();

try {
    // 1. Update the main package info in the `packages` table
    $sql_package = "UPDATE packages SET name = ?, price = ?, points_value = ? WHERE id = ?";
    $stmt_package = $mysqli->prepare($sql_package);
    $stmt_package->bind_param("sdii", $name, $price, $points_value, $package_id);
    $stmt_package->execute();
    $stmt_package->close();
    
    // 2. Delete all existing product inclusions for this package
    $sql_delete = "DELETE FROM package_products WHERE package_id = ?";
    $stmt_delete = $mysqli->prepare($sql_delete);
    $stmt_delete->bind_param("i", $package_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 3. Insert the new product inclusions
    $sql_inclusions = "INSERT INTO package_products (package_id, product_id, quantity) VALUES (?, ?, ?)";
    $stmt_inclusions = $mysqli->prepare($sql_inclusions);
    
    foreach ($product_ids as $index => $product_id) {
        $quantity = (int)$quantities[$index];
        if ($product_id > 0 && $quantity > 0) {
            $stmt_inclusions->bind_param("iii", $package_id, $product_id, $quantity);
            $stmt_inclusions->execute();
        }
    }
    $stmt_inclusions->close();
    
    // All good, commit the transaction
    $mysqli->commit();
    $_SESSION['message'] = "Package updated successfully.";
    $_SESSION['msg_type'] = "success";

} catch (Exception $e) {
    // Something went wrong, roll back
    $mysqli->rollback();
    $_SESSION['message'] = "Error updating package: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}

$mysqli->close();
header('Location: package_management.php');
exit();