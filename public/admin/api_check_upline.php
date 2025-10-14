<?php
session_start();
header('Content-Type: application/json');
require_once '../../src/includes/db_connect.php';

if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['valid' => false, 'message' => 'Auth failed.']);
    exit();
}

$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$position = isset($_GET['position']) ? trim($_GET['position']) : '';
$member_id_to_move = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0; // The member being edited

$response = ['valid' => false, 'message' => ''];

if (empty($username) || empty($position)) {
    echo json_encode(['valid' => false, 'message' => 'Username and position are required.']);
    exit();
}

// 1. Find the upline's ID from their username
$stmt_upline = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
$stmt_upline->bind_param("s", $username);
$stmt_upline->execute();
$result_upline = $stmt_upline->get_result();
if ($upline = $result_upline->fetch_assoc()) {
    $upline_id = $upline['id'];
    
    // An admin cannot make a user their own upline
    if ($upline_id == $member_id_to_move) {
        $response['message'] = 'A member cannot be their own upline.';
        echo json_encode($response);
        exit();
    }

    // 2. Check if the spot under this upline is vacant
    $stmt_spot = $mysqli->prepare("SELECT user_id FROM genealogy_tree WHERE upline_id = ? AND position = ?");
    $stmt_spot->bind_param("is", $upline_id, $position);
    $stmt_spot->execute();
    if ($stmt_spot->get_result()->num_rows === 0) {
        $response['valid'] = true;
        $response['message'] = "✓ Spot is available under '$username'";
        $response['upline_id'] = $upline_id; // Send back the ID
    } else {
        $response['message'] = "✗ '$position' position is already taken under '$username'";
    }
    $stmt_spot->close();

} else {
    $response['message'] = "✗ Upline username '$username' not found.";
}
$stmt_upline->close();

echo json_encode($response);
$mysqli->close();
?>