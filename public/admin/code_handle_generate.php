<?php
session_start();
require_once '../../src/includes/db_connect.php';

// Security
if (($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: activation_codes.php');
    exit();
}

$package_id = (int)$_POST['package_id'];
$quantity = (int)$_POST['quantity'];
$generator_id = (int)$_SESSION['user_id'];

// Validation
if ($package_id <= 0 || $quantity <= 0 || $quantity > 100) {
    $_SESSION['message'] = "Invalid input provided.";
    $_SESSION['msg_type'] = "danger";
    header('Location: code_generator.php');
    exit();
}

$sql = "INSERT INTO activation_codes (code, package_id, generated_by_id) VALUES (?, ?, ?)";
if ($stmt = $mysqli->prepare($sql)) {
    $count = 0;
    for ($i = 0; $i < $quantity; $i++) {
        // Generate a more readable, unique code
        $code = 'RMG-' . strtoupper(bin2hex(random_bytes(3))) . '-' . strtoupper(bin2hex(random_bytes(3)));
        
        $stmt->bind_param("sii", $code, $package_id, $generator_id);
        if ($stmt->execute()) {
            $count++;
        }
    }
    
    $_SESSION['message'] = "$count code(s) generated successfully.";
    $_SESSION['msg_type'] = "success";
    $stmt->close();

} else {
    $_SESSION['message'] = "Database error.";
    $_SESSION['msg_type'] = "danger";
}

$mysqli->close();
header('Location: activation_codes.php');
exit();