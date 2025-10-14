<?php
session_start();
require_once '../../src/includes/db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'member' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php'); exit();
}

$member_id = $_SESSION['user_id'];
$left_points = isset($_POST['left_points']) ? (int)$_POST['left_points'] : 0;
$right_points = isset($_POST['right_points']) ? (int)$_POST['right_points'] : 0;
$total_placed = $left_points + $right_points;

// --- Validation ---
if ($total_placed <= 0) {
    $_SESSION['message'] = "You must place at least 1 point."; $_SESSION['msg_type'] = "danger";
    header('Location: redundant_binary.php'); exit();
}

$mysqli->begin_transaction();
try {
    // 1. Get the member's current redundant balance (and lock the row for the transaction)
    $stmt_get = $mysqli->prepare("SELECT redundant_points_balance FROM member_profiles WHERE user_id = ? FOR UPDATE");
    $stmt_get->bind_param("i", $member_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    $balance = 0;
    if ($row = $result->fetch_assoc()) {
        $balance = (int)$row['redundant_points_balance'];
    }
    $stmt_get->close();

    if ($total_placed > $balance) {
        throw new Exception("You tried to place more points than you have available.");
    }
    
    // 2. Insert points into the LEFT leg if applicable
    if ($left_points > 0) {
        $stmt_left = $mysqli->prepare("INSERT INTO binary_points (user_id, points, position, source_user_id) VALUES (?, ?, 'L', ?)");
        $stmt_left->bind_param("iii", $member_id, $left_points, $member_id); // Points sourced from self
        $stmt_left->execute();
        $stmt_left->close();
    }
    
    // 3. Insert points into the RIGHT leg if applicable
    if ($right_points > 0) {
        $stmt_right = $mysqli->prepare("INSERT INTO binary_points (user_id, points, position, source_user_id) VALUES (?, ?, 'R', ?)");
        $stmt_right->bind_param("iii", $member_id, $right_points, $member_id);
        $stmt_right->execute();
        $stmt_right->close();
    }

    // 4. Deduct the placed points from the member's balance
    $stmt_update = $mysqli->prepare("UPDATE member_profiles SET redundant_points_balance = redundant_points_balance - ? WHERE user_id = ?");
    $stmt_update->bind_param("ii", $total_placed, $member_id);
    $stmt_update->execute();
    $stmt_update->close();

    $mysqli->commit();
    $_SESSION['message'] = "Successfully placed $left_points points on your left leg and $right_points points on your right leg.";
    $_SESSION['msg_type'] = "success";

} catch (Exception $e) {
    $mysqli->rollback();
    $_SESSION['message'] = "An error occurred: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}

$mysqli->close();
header('Location: redundant_binary.php');
exit();