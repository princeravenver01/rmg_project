<?php
session_start();
header('Content-Type: application/json');
require_once '../../src/includes/db_connect.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    echo json_encode(['exists' => false, 'error' => 'auth']);
    exit();
}

$member_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$response = ['exists' => false];

if ($member_id > 0) {
    $stmt = $mysqli->prepare("SELECT name FROM users WHERE id = ? AND role = 'member'");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $response['exists'] = true;
        $response['name'] = $row['name'];
    }
    $stmt->close();
}

echo json_encode($response);
?>