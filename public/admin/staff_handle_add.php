<?php
session_start();
require_once '../../src/includes/db_connect.php';

// --- Security Check: Only Admins can process this form and must be a POST request ---
if ($_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Set an error message and redirect
    $_SESSION['message'] = "Unauthorized action.";
    $_SESSION['msg_type'] = "danger";
    header('Location: staff_management.php');
    exit();
}

// --- 1. Get and Sanitize Form Data ---
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// --- 2. Perform Validation ---
// Check if passwords match
if ($password !== $confirm_password) {
    $_SESSION['message'] = "Passwords do not match.";
    $_SESSION['msg_type'] = "danger";
    header('Location: staff_add.php');
    exit();
}

// Check for strong password (example: at least 8 characters)
if (strlen($password) < 8) {
    $_SESSION['message'] = "Password must be at least 8 characters long.";
    $_SESSION['msg_type'] = "danger";
    header('Location: staff_add.php');
    exit();
}

// --- 3. Check if Email Already Exists ---
$sql_check = "SELECT id FROM users WHERE email = ?";
if ($stmt_check = $mysqli->prepare($sql_check)) {
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        // Email already exists
        $_SESSION['message'] = "An account with this email already exists.";
        $_SESSION['msg_type'] = "danger";
        header('Location: staff_add.php');
        exit();
    }
    $stmt_check->close();
}


// --- 4. Hash the Password ---
// This is a crucial security step.
$hashed_password = password_hash($password, PASSWORD_DEFAULT);


// --- 5. Insert into Database using a Prepared Statement ---
$sql_insert = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'staff')";

if ($stmt_insert = $mysqli->prepare($sql_insert)) {
    // Bind variables to the prepared statement as parameters
    $stmt_insert->bind_param("sss", $name, $email, $hashed_password);
    
    // Attempt to execute the prepared statement
    if ($stmt_insert->execute()) {
        // --- SUCCESS ---
        $_SESSION['message'] = "New staff member has been added successfully.";
        $_SESSION['msg_type'] = "success";
        header('Location: staff_management.php');
    } else {
        // --- FAILURE ---
        $_SESSION['message'] = "Oops! Something went wrong. Please try again later.";
        $_SESSION['msg_type'] = "danger";
        header('Location: staff_add.php');
    }

    // Close statement
    $stmt_insert->close();
}

// Close connection
$mysqli->close();
exit();
?>