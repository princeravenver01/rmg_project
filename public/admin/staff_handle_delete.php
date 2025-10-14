<?php
session_start();
require_once '../../src/includes/db_connect.php';

// Security: Admin role and POST request
if ($_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Unauthorized action.";
    $_SESSION['msg_type'] = "danger";
    header('Location: staff_management.php');
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id > 0) {
    // It's good practice not to delete super admins or oneself.
    // For now, we just ensure it's a staff role.
    $sql = "DELETE FROM users WHERE id = ? AND role = 'staff'";
    
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = "Staff member deleted successfully.";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['message'] = "Staff member not found or could not be deleted.";
                $_SESSION['msg_type'] = "danger";
            }
        } else {
            $_SESSION['message'] = "Error deleting record.";
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    }
} else {
    $_SESSION['message'] = "Invalid ID provided.";
    $_SESSION['msg_type'] = "danger";
}

$mysqli->close();
header('Location: staff_management.php');
exit();