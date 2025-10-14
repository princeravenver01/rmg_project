<?php
session_start();
require_once '../src/includes/db_connect.php'; // Make sure this path is correct

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

// Ensure username and password are set
if (!isset($_POST['username'], $_POST['password'])) {
    $_SESSION['error'] = "Please enter both username and password.";
    header("Location: login.php");
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

// Query for a user with the role of 'member'
$sql = "SELECT id, name, password, role FROM users WHERE username = ? AND role = 'member'";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("s", $username);
    
    if ($stmt->execute()) {
        $stmt->store_result();
        
        // Check if exactly one user was found
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $name, $hashed_password, $role);
            
            if ($stmt->fetch()) {
                // Now, verify the password
                if (password_verify($password, $hashed_password)) {
                    // --- SUCCESS ---
                    session_regenerate_id(true); // Prevent session fixation
                    
                    // Set session variables
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_id'] = $id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['role'] = $role;
                    
                    // Redirect to the member's dashboard
                    header("Location: member/dashboard.php");
                    exit; // IMPORTANT: Stop script execution after redirect
                } else {
                    // Password was incorrect
                    $_SESSION['error'] = "Invalid username or password.";
                    header("Location: login.php");
                    exit;
                }
            }
        } else {
            // User was not found or more than one user found (which shouldn't happen with unique username)
            $_SESSION['error'] = "Invalid username or password.";
            header("Location: login.php");
            exit;
        }
    } else {
        // SQL execution failed
        $_SESSION['error'] = "An error occurred. Please try again.";
        header("Location: login.php");
        exit;
    }
    
    $stmt->close();
} else {
    // This handles a database error (e.g., query failed to prepare)
    // In production, you might want to log this error instead of showing a generic message
    $_SESSION['error'] = "A database error occurred. Please try again later.";
    header("Location: login.php");
    exit;
}

$mysqli->close();
?>