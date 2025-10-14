<?php
session_start();
header('Content-Type: application/json');
require_once '../../src/includes/db_connect.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    echo json_encode(['error' => 'Unauthorized']); exit();
}

$code_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($code_id <= 0) {
    echo json_encode(['error' => 'Invalid ID']); exit();
}

$sql = "SELECT ac.code, ac.status, ac.created_at, p.name as package_name, u.name as generator_name 
        FROM activation_codes ac
        JOIN packages p ON ac.package_id = p.id
        JOIN users u ON ac.generated_by_id = u.id
        WHERE ac.id = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $code_id);
$stmt->execute();
$result = $stmt->get_result();
if ($data = $result->fetch_assoc()) {
    // Format the data for display
    $data['status'] = ucfirst($data['status']);
    $data['created_at'] = date('m/d/Y', strtotime($data['created_at']));
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Code details not found.']);
}
$stmt->close();
$mysqli->close();
?>