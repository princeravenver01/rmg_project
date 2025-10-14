<?php
header('Content-Type: application/json');
require_once '../src/includes/db_connect.php';

$response = ['available' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';

    if (!empty($username) && strlen($username) >= 4) {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            $response['available'] = true;
        }
        $stmt->close();
    }
}

echo json_encode($response);
$mysqli->close();
?>