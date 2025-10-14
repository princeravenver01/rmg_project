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

// Get form data
$id = (int)$_POST['id'];
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$password = $_POST['password'];

// --- Validation ---
if ($id <= 0 || empty($name) || empty($email)) {
    $_SESSION['message'] = "Invalid data provided.";
    $_SESSION['msg_type'] = "danger";
    header('Location: staff_management.php');
    exit();
}

// Check if email already exists for ANOTHER user
$sql_check = "SELECT id FROM users WHERE email = ? AND id != ?";
if ($stmt_check = $mysqli->prepare($sql_check)) {
    $stmt_check->bind_param("si", $email, $id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $_SESSION['message'] = "This email is already in use by another account.";
        $_SESSION['msg_type'] = "danger";
        header('Location: staff_edit.php?id=' . $id);
        exit();
    }
    $stmt_check->close();
}

// --- Prepare the UPDATE query ---
// If password is provided, we update it. If not, we only update name and email.
if (!empty($password)) {
    // Password validation
    if (strlen($password) < 8) {
        $_SESSION['message'] = "Password must be at least 8 characters long.";
        $_SESSION['msg_type'] = "danger";
        header('Location: staff_edit.php?id=' . $id);
        exit();
    }
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql_update = "UPDATE users SET name = ?, email = ?, password = ? WHERE id = ? AND role = 'staff'";
    $types = "sssi";
    $params = [&$name, &$email, &$hashed_password, &$id];
} else {
    // No password change
    $sql_update = "UPDATE users SET name = ?, email = ? WHERE id = ? AND role = 'staff'";
    $types = "ssi";
    $params = [&$name, &$email, &$id];
}

// --- Execute the update ---
if ($stmt_update = $mysqli->prepare($sql_update)) {
    // Use call_user_func_array to bind params dynamically
    call_user_func_array([$stmt_update, 'bind_param'], array_merge([$types], $params));

    if ($stmt_update->execute()) {
        $_SESSION['message'] = "Staff member updated successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating record.";
        $_SESSION['msg_type'] = "danger";
    }
    $stmt_update->close();
}

$mysqli->close();
header('Location: staff_management.php');
exit();