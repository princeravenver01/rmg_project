<?php
session_start();
require_once '../../src/includes/db_connect.php';

// Security
if ($_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: package_management.php');
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id > 0) {
    // Thanks to ON DELETE CASCADE, deleting from `packages` will also
    // remove all corresponding rows from `package_products`.
    $sql = "DELETE FROM packages WHERE id = ?";
    
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['message'] = "Package deleted successfully.";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Could not delete package. It may have already been removed.";
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    }
} else {
    $_SESSION['message'] = "Invalid ID provided.";
    $_SESSION['msg_type'] = "danger";
}

$mysqli->close();
header('Location: package_management.php');
exit();