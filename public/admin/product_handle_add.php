<?php
session_start();
require_once '../../src/includes/db_connect.php';

// Security: Admin role and POST request
if ($_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: product_management.php');
    exit();
}

$unilevel_bonus = $_POST['unilevel_bonus'];
// --- 1. Handle File Upload ---
$image_path = null;
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
    $target_dir = "../uploads/"; // The folder where images will be saved
    // Create a unique filename to prevent overwriting existing files
    $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
    $unique_filename = uniqid('product_', true) . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;
    
    // Validate file type and size (e.g., max 2MB)
    $allowed_types = ['jpg', 'jpeg', 'png'];
    if (in_array(strtolower($file_extension), $allowed_types) && $_FILES['product_image']['size'] <= 2000000) {
        // Move the uploaded file to the target directory
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            // Save the relative path to the database (without the leading '../')
            $image_path = 'uploads/' . $unique_filename;
        }
    }
}

// --- 2. Get Form Data ---
$name = trim($_POST['name']);
$member_price = $_POST['member_price'];
$srp = $_POST['srp'];
$points_value = (int)$_POST['points_value'];
$stock_quantity = (int)$_POST['stock_quantity'];
$description = trim($_POST['description']);
$manufacturer = trim($_POST['manufacturer']);
$barcode = trim($_POST['barcode']);

// --- 3. Insert into Database ---
$sql = "INSERT INTO products (name, description, manufacturer, member_price, srp, points_value, unilevel_bonus, barcode, image_path, stock_quantity) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("sssdddissi", 
        $name, 
        $description, 
        $manufacturer, 
        $member_price, 
        $srp, 
        $points_value, 
        $unilevel_bonus,
        $barcode, 
        $image_path, 
        $stock_quantity
    );
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Product added successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error: Could not add product.";
        $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();
} else {
    $_SESSION['message'] = "Database error: could not prepare statement.";
    $_SESSION['msg_type'] = "danger";
}

$mysqli->close();
header('Location: product_management.php');
exit();