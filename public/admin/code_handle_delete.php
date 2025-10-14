<?php
session_start();
require_once '../../src/includes/db_connect.php';

// Security: Admins only, POST request
if ($_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: activation_codes.php');
    exit();
}

$id = (int)$_POST['id'];

if ($id > 0) {
    // We only allow deleting 'available' codes to prevent accidental removal of used codes
    $sql = "DELETE FROM activation_codes WHERE id = ? AND status = 'available'";
    
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['message'] = "Activation code deleted successfully.";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Could not delete code. It might be in use or already deleted.";
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    }
}

$mysqli->close();
header('Location: activation_codes.php');
exit();