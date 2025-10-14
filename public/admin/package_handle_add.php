<?php
session_start();
require_once '../../src/includes/db_connect.php';

// Security
if ($_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: package_management.php');
    exit();
}

// Get basic package data
$name = trim($_POST['name']);
$price = $_POST['price'];
$points_value = (int)$_POST['points_value'];
$product_ids = $_POST['product_id'];
$quantities = $_POST['quantity'];

// --- Start Database Transaction ---
$mysqli->begin_transaction();

try {
    // 1. Insert the main package into the `packages` table
    $sql_package = "INSERT INTO packages (name, price, points_value) VALUES (?, ?, ?)";
    $stmt_package = $mysqli->prepare($sql_package);
    $stmt_package->bind_param("sdi", $name, $price, $points_value);
    $stmt_package->execute();
    
    // Get the ID of the newly inserted package
    $package_id = $mysqli->insert_id;
    
    if ($package_id <= 0) {
        throw new Exception("Failed to create package.");
    }
    
    // 2. Insert the product inclusions into the `package_products` table
    $sql_inclusions = "INSERT INTO package_products (package_id, product_id, quantity) VALUES (?, ?, ?)";
    $stmt_inclusions = $mysqli->prepare($sql_inclusions);
    
    foreach ($product_ids as $index => $product_id) {
        $quantity = (int)$quantities[$index];
        if ($product_id > 0 && $quantity > 0) {
            $stmt_inclusions->bind_param("iii", $package_id, $product_id, $quantity);
            $stmt_inclusions->execute();
        }
    }
    
    // If we reach here, all queries were successful. Commit the transaction.
    $mysqli->commit();
    $_SESSION['message'] = "Package created successfully.";
    $_SESSION['msg_type'] = "success";

} catch (Exception $e) {
    // An error occurred, roll back the transaction
    $mysqli->rollback();
    $_SESSION['message'] = "Error creating package: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}

$stmt_package->close();
$stmt_inclusions->close();
$mysqli->close();

header('Location: package_management.php');
exit();