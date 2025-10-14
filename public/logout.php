<?php
session_start();
 
// Unset all session variables
$_SESSION = array();
 
// Destroy the session
session_destroy();
 
// Redirect to the member login page by default
header("location: login.php");
exit;