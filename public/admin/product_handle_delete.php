<?php
session_start();
require_once '../../src/includes/db_connect.php';

// Security
if ($_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: product_management.php');
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id > 0) {
    // First, get the image path from the database to delete the file
    $stmt_select = $mysqli->prepare("SELECT image_path FROM products WHERE id = ?");
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $image_to_delete = null;
    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
        $image_to_delete = $product['image_path'];
    }
    $stmt_select->close();

    // Now, delete the record from the database
    $stmt_delete = $mysqli->prepare("DELETE FROM products WHERE id = ?");
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        if ($stmt_delete->affected_rows > 0) {
            // If DB deletion was successful, delete the image file
            if (!empty($image_to_delete) && file_exists('../' . $image_to_delete)) {
                unlink('../' . $image_to_delete);
            }
            $_SESSION['message'] = "Product deleted successfully.";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Product not found.";
            $_SESSION['msg_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Error deleting product.";
        $_SESSION['msg_type'] = "danger";
    }
    $stmt_delete->close();
} else {
    $_SESSION['message'] = "Invalid ID.";
    $_SESSION['msg_type'] = "danger";
}

$mysqli->close();
header('Location: product_management.php');
exit();