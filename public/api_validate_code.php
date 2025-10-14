<?php
session_start();
header('Content-Type: application/json');
require_once '../src/includes/db_connect.php';

$response = ['valid' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = isset($_POST['activation_code']) ? trim($_POST['activation_code']) : '';

    if (empty($code)) {
        $response['message'] = 'Activation code cannot be empty.';
    } else {
        $stmt = $mysqli->prepare("SELECT id FROM activation_codes WHERE code = ? AND status = 'available'");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 1) {
            $response['valid'] = true;
            $response['message'] = 'Code is valid!';
        } else {
            $response['message'] = 'Invalid or already used activation code.';
        }
        $stmt->close();
    }
}

echo json_encode($response);
$mysqli->close();
?>