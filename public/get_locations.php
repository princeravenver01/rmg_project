<?php
header('Content-Type: application/json');
require_once '../src/includes/db_connect.php'; // Use the RMG DB connect, as it has the credentials

// We need to connect to the Tribute Grace database to get the location tables
$tg_conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, 'kasikas_official');
if ($tg_conn->connect_error) {
    echo json_encode(['error' => 'Location service unavailable.']);
    exit();
}

$type = $_GET['type'] ?? '';
$response = [];

try {
    if ($type === 'provinces') {
        $result = $tg_conn->query("SELECT provCode, provDesc FROM refprovince ORDER BY provDesc ASC");
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
    } elseif ($type === 'cities' && isset($_GET['prov_code'])) {
        $stmt = $tg_conn->prepare("SELECT citymunCode, citymunDesc FROM refcitymun WHERE provCode = ? ORDER BY citymunDesc ASC");
        $stmt->bind_param("s", $_GET['prov_code']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
        $stmt->close();
    } elseif ($type === 'barangays' && isset($_GET['city_code'])) {
        $stmt = $tg_conn->prepare("SELECT brgyCode, brgyDesc FROM refbrgy WHERE citymunCode = ? ORDER BY brgyDesc ASC");
        $stmt->bind_param("s", $_GET['city_code']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $response = ['error' => 'An error occurred while fetching locations.'];
}

$tg_conn->close();
echo json_encode($response);
?>