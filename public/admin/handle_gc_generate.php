<?php
session_start();
require_once '../../src/includes/db_connect.php';
if ($_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: manage_gcs.php'); exit(); }

$member_id = (int)$_POST['member_id'];
$amount = (float)$_POST['amount'];

// Validation
if ($member_id <= 0 || $amount <= 0) {
    $_SESSION['message'] = "Invalid Member ID or Amount."; $_SESSION['msg_type'] = "danger";
    header('Location: manage_gcs.php'); exit();
}

// Check if member exists
$stmt_mem = $mysqli->prepare("SELECT id FROM users WHERE id = ? AND role = 'member'");
$stmt_mem->bind_param("i", $member_id);
$stmt_mem->execute();
if ($stmt_mem->get_result()->num_rows === 0) {
    $_SESSION['message'] = "Member with ID $member_id not found."; $_SESSION['msg_type'] = "danger";
    header('Location: manage_gcs.php'); exit();
}
$stmt_mem->close();

// Generate and insert
$gc_code = 'MGC-' . strtoupper(bin2hex(random_bytes(6))); // MGC for Manual GC
$stmt_gc = $mysqli->prepare("INSERT INTO gift_certificates (code, user_id, amount) VALUES (?, ?, ?)");
$stmt_gc->bind_param("sid", $gc_code, $member_id, $amount);

if ($stmt_gc->execute()) {
    $_SESSION['message'] = "Successfully generated GC '$gc_code' for Member ID $member_id.";
    $_SESSION['msg_type'] = "success";
} else {
    $_SESSION['message'] = "Database error: Could not generate GC.";
    $_SESSION['msg_type'] = "danger";
}
$stmt_gc->close();
$mysqli->close();
header('Location: manage_gcs.php');
exit();