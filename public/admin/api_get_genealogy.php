<?php
session_start();
header('Content-Type: application/json');
require_once '../../src/includes/db_connect.php';

if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    echo json_encode(['error' => 'Authentication required.']);
    exit();
}

// Default to the Corp Account ID (e.g., 20)
$start_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 20; 

function getGenealogyRecursive($mysqli, $parent_id, $current_level, $max_depth) {
    if ($current_level > $max_depth) return null;

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

$genealogy_data = getGenealogyRecursive($mysqli, $start_user_id, 1, 3);
$mysqli->close();
echo json_encode($genealogy_data);
?>