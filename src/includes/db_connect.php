<?php
require_once __DIR__ . '/../../vendor/autoload.php';
/*
 * This file connects the application to the MySQL database.
 * It will be included in every file that needs to interact with the database.
 */

// --- Database Configuration ---
// These are the default credentials for a standard XAMPP installation.
// Change them if your setup is different.
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Default XAMPP username
define('DB_PASSWORD', '');     // Default XAMPP password is empty
define('DB_NAME', 'rmg_database');

// --- Establish the Connection ---
// We use the MySQLi object-oriented approach.
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// --- Check Connection ---
// If the connection object returns an error number, it means the connection failed.
if ($mysqli->connect_errno) {
    // Stop the script and display a detailed error message.
    // In a live production environment, you would log this error instead of showing it to the user.
    die("ERROR: Failed to connect to MySQL: " . $mysqli->connect_error);
}

// Optional: Set character set to utf8mb4 for full Unicode support
$mysqli->set_charset("utf8mb4");

?>