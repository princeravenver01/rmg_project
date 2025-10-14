<?php
session_start();
require_once '../../src/includes/db_connect.php';

// Security
if ($_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: product_management.php');
    exit();
}

$unilevel_bonus = $_POST['unilevel_bonus'];
$id = (int)$_POST['id'];
$current_image = $_POST['current_image'];
$image_path = $current_image; // Default to the current image

// --- Handle File Upload (if a new file is provided) ---
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
    $target_dir = "../uploads/";
    $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
    $unique_filename = uniqid('product_', true) . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;
    
    $allowed_types = ['jpg', 'jpeg', 'png'];
    if (in_array(strtolower($file_extension), $allowed_types) && $_FILES['product_image']['size'] <= 2000000) {
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            // New image uploaded successfully, set the new path
            $image_path = 'uploads/' . $unique_filename;
            
            // Delete the old image file if it exists
            if (!empty($current_image) && file_exists('../' . $current_image)) {
                unlink('../' . $current_image);
            }
        }
    }
}

// --- Get other form data ---
$name = trim($_POST['name']);
$member_price = $_POST['member_price'];
$srp = $_POST['srp'];
$points_value = (int)$_POST['points_value'];
$stock_quantity = (int)$_POST['stock_quantity'];
$description = trim($_POST['description']);
$manufacturer = trim($_POST['manufacturer']);
$barcode = trim($_POST['barcode']);

// --- Update Database ---
$sql = "UPDATE products SET 
            name = ?, 
            description = ?, 
            manufacturer = ?, 
            member_price = ?, 
            srp = ?, 
            points_value = ?, 
            unilevel_bonus = ?,
            barcode = ?, 
            image_path = ?, 
            stock_quantity = ? 
        WHERE id = ?";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("sssdddissii", 
        $name, $description, $manufacturer, $member_price, $srp, 
        $points_value, $unilevel_bonus, $barcode, $image_path, $stock_quantity, $id
    );
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Product updated successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error: Could not update product.";
        $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();
} else {
    $_SESSION['message'] = "Database error.";
    $_SESSION['msg_type'] = "danger";
}

$mysqli->close();
header('Location: product_management.php');
exit();