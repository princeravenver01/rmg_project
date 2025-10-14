<?php
session_start();
require_once '../../src/includes/db_connect.php';
if ($_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: manage_gcs.php'); exit(); }

$gc_id = (int)$_POST['id'];
$amount = (float)$_POST['amount'];

if ($gc_id <= 0 || $amount <= 0) { /* ... error handling ... */ }

// IMPORTANT: Only allow editing of ACTIVE certificates
$stmt = $mysqli->prepare("UPDATE gift_certificates SET amount = ? WHERE id = ? AND status = 'active'");
$stmt->bind_param("di", $amount, $gc_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "GC amount updated successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Could not update GC. It may be already used or does not exist.";
        $_SESSION['msg_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "Database error.";
    $_SESSION['msg_type'] = "danger";
}

$stmt->close();
$mysqli->close();
header('Location: manage_gcs.php');
exit();