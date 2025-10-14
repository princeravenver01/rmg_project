<?php
// Start the session to store login information
session_start();

// Include the database connection file
// The path needs to go up two directories to reach the 'src' folder
require_once '../../src/includes/db_connect.php';

// --- Form Submission Check ---
// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Input Validation ---
    // Get email and password from the form, trim whitespace
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // --- Database Query with Prepared Statement (Security First!) ---
    // 1. Prepare the SQL query to prevent SQL injection
    $sql = "SELECT id, name, password, role FROM users WHERE email = ? AND (role = 'admin' OR role = 'staff')";
    
    if ($stmt = $mysqli->prepare($sql)) {
        // 2. Bind the email variable to the placeholder in the prepared statement
        $stmt->bind_param("s", $email);

        // 3. Execute the statement
        if ($stmt->execute()) {
            // 4. Store the result so we can check if a user was found
            $stmt->store_result();

            // 5. Check if exactly one user was found
            if ($stmt->num_rows == 1) {
                // 6. Bind the result variables
                $stmt->bind_result($id, $name, $hashed_password, $role);
                
                // 7. Fetch the results
                if ($stmt->fetch()) {
                    // 8. Verify the submitted password against the hashed password from the database
                    if (password_verify($password, $hashed_password)) {
                        
                        // --- SUCCESSFUL LOGIN ---
                        // Password is correct, so start a new session
                        session_regenerate_id(); // Security measure to prevent session fixation

                        // Store data in session variables
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_id'] = $id;
                        $_SESSION['user_name'] = $name;
                        $_SESSION['role'] = $role;

                        // Redirect user to the admin dashboard
                        header("location: dashboard.php");
                        exit; // Important to exit after a redirect

                    } else {
                        // Password is not valid
                        $_SESSION['login_error'] = "Invalid email or password.";
                        header("location: index.php");
                        exit;
                    }
                }
            } else {
                // No user found with that email and role
                $_SESSION['login_error'] = "Invalid email or password.";
                header("location: index.php");
                exit;
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }

        // Close the statement
        $stmt->close();
    }
    
    // Close the database connection
    $mysqli->close();

} else {
    // If someone tries to access this file directly without POSTing data, redirect them.
    header("location: index.php");
    exit;
}
?>