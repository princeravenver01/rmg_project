<?php

echo "<h1>Password Hash Test</h1>";

// --- 1. Include the database connection ---
require_once '../../src/includes/db_connect.php';
echo "<p>Database connection included.</p>";

// --- 2. Define the password we are testing ---
$plainPasswordToTest = 'password123';
echo "<p>Testing against password: '" . $plainPasswordToTest . "'</p>";

// --- 3. Fetch the hashed password from the database ---
$sql = "SELECT password FROM users WHERE id = 1";
$result = $mysqli->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $hashedPasswordFromDB = $user['password'];
    
    echo "<p>Hashed password found in DB: <code>" . htmlspecialchars($hashedPasswordFromDB) . "</code></p>";

    // --- 4. Perform the verification ---
    echo "<p>Running password_verify()...</p>";
    
    if (password_verify($plainPasswordToTest, $hashedPasswordFromDB)) {
        echo '<h2 style="color: green;">SUCCESS: The passwords MATCH!</h2>';
        echo "<p>Login should be working. If it is not, there is a logic error in handle_login.php.</p>";
    } else {
        echo '<h2 style="color: red;">FAILURE: The passwords DO NOT MATCH!</h2>';
        echo "<p>This confirms the hash stored in your database is incorrect for 'password123'.</p>";
    }

} else {
    echo '<h2 style="color: red;">ERROR: Could not find user with ID = 1.</h2>';
}

$mysqli->close();

?>