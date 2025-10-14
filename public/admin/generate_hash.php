<?php
$passwordToHash = 'password123';
$newHash = password_hash($passwordToHash, PASSWORD_DEFAULT);
echo "<h1>New Hash for 'password123'</h1>";
echo "<p>Copy this entire string and paste it into the 'password' field for your admin user in phpMyAdmin:</p>";
echo "<hr>";
echo "<code>" . $newHash . "</code>";
?>