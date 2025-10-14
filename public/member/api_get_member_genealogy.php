<?php
session_start();
header('Content-Type: application/json');
require_once '../../src/includes/db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'member') {
    echo json_encode(['error' => 'Member authentication required.']);
    exit();
}

// Helper function to check if a user is in the downline
function isUserInDownline($mysqli, $loggedInUserId, $targetUserId) {
    if ($loggedInUserId == $targetUserId) return true;
    $currentUser = $targetUserId;
    $safetyCounter = 0;
    while ($currentUser > 0 && $safetyCounter < 50) {
        $stmt = $mysqli->prepare("SELECT upline_id FROM genealogy_tree WHERE user_id = ?");
        $stmt->bind_param("i", $currentUser);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['upline_id'] == $loggedInUserId) return true;
            $currentUser = $row['upline_id'];
        } else { return false; }
        $stmt->close();
        $safetyCounter++;
    }
    return false;
}

$start_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];
$logged_in_user_id = $_SESSION['user_id'];

if (!isUserInDownline($mysqli, $logged_in_user_id, $start_user_id)) {
    $start_user_id = $logged_in_user_id;
}


// --- THIS IS THE CRITICAL FIX: The full, correct recursive function ---
function getGenealogyRecursive($mysqli, $parent_id, $current_level, $max_depth) {
    if ($current_level > $max_depth) return null;

    // Use the same detailed query as the admin API to get all necessary data
    $sql_parent = "
        SELECT u.id, u.name, u.username, u.account_type, u.is_active, gt.position, gt.upline_id,
               mp.debt_balance, p.price as package_price
        FROM users u 
        LEFT JOIN genealogy_tree gt ON u.id = gt.user_id 
        LEFT JOIN member_profiles mp ON u.id = mp.user_id
        LEFT JOIN activation_codes ac ON u.id = ac.used_by_id
        LEFT JOIN packages p ON ac.package_id = p.id
        WHERE u.id = ?
    ";
    $stmt_parent = $mysqli->prepare($sql_parent);
    $stmt_parent->bind_param("i", $parent_id);
    $stmt_parent->execute();
    $parent_result = $stmt_parent->get_result();
    if ($parent_result->num_rows === 0) return null;
    $node = $parent_result->fetch_assoc();
    $stmt_parent->close();
    
    if (!isset($node['position'])) $node['position'] = null;
    if (!isset($node['upline_id'])) $node['upline_id'] = null;

    $stmt_points = $mysqli->prepare("SELECT SUM(CASE WHEN position = 'L' THEN points ELSE 0 END) AS left_points, SUM(CASE WHEN position = 'R' THEN points ELSE 0 END) AS right_points FROM binary_points WHERE user_id = ? AND status = 'unprocessed'");
    $stmt_points->bind_param("i", $node['id']);
    $stmt_points->execute();
    $points_data = $stmt_points->get_result()->fetch_assoc();
    $node['left_points'] = (int)($points_data['left_points'] ?? 0);
    $node['right_points'] = (int)($points_data['right_points'] ?? 0);
    $stmt_points->close();

    $stmt_check = $mysqli->prepare("SELECT id FROM genealogy_tree WHERE upline_id = ? LIMIT 1");
    $stmt_check->bind_param("i", $node['id']);
    $stmt_check->execute();
    $stmt_check->store_result();
    $node['has_children'] = $stmt_check->num_rows > 0;
    $stmt_check->close();
    
    $node['children'] = [];

    if ($current_level < $max_depth) {
        $stmt_children = $mysqli->prepare("SELECT user_id FROM genealogy_tree WHERE upline_id = ?");
        $stmt_children->bind_param("i", $parent_id);
        $stmt_children->execute();
        $children_result = $stmt_children->get_result();
        while ($child_row = $children_result->fetch_assoc()) {
            $child_node = getGenealogyRecursive($mysqli, $child_row['user_id'], $current_level + 1, $max_depth);
            if ($child_node) {
                $node['children'][] = $child_node;
            }
        }
        $stmt_children->close();
    }
    return $node;
}
// --- END OF FIX ---

$genealogy_data = getGenealogyRecursive($mysqli, $start_user_id, 1, 3);

$mysqli->close();
echo json_encode($genealogy_data);
?>