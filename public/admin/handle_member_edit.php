<?php
session_start();
require_once '../../src/includes/db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: member_management.php');
    exit();
}

// --- NEW HELPER FUNCTION TO GET ALL USER IDs IN A DOWNLINE ---
function getDownlineIds($mysqli, $parent_id) {
    $ids = [];
    $queue = [$parent_id];
    $visited = []; // To prevent infinite loops in case of data corruption

    while (!empty($queue)) {
        $current_id = array_shift($queue);
        if (in_array($current_id, $visited)) continue;
        
        $visited[] = $current_id;
        $ids[] = $current_id;

        $stmt = $mysqli->prepare("SELECT user_id FROM genealogy_tree WHERE upline_id = ?");
        $stmt->bind_param("i", $current_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $queue[] = $row['user_id'];
        }
        $stmt->close();
    }
    return $ids;
}

// --- Get All Form Data (Your existing code) ---
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = $_POST['password'] ?? '';
$new_upline_id = isset($_POST['upline_id']) ? (int)$_POST['upline_id'] : -1;
$new_position = isset($_POST['position']) ? trim($_POST['position']) : '';


// --- Your Existing Validation (This is all correct) ---
if ($id <= 0 || empty($name) || empty($username) || empty($email) || $new_upline_id === -1 || !in_array($new_position, ['L', 'R'])) {
    // ...
}
// ... (Check for duplicate username)
// ... (Check for duplicate email)
// ... (Final check on the vacancy of the new spot)


// --- START TRANSACTION ---
$mysqli->begin_transaction();
try {
    // --- 1. Determine if placement is changing (your existing logic) ---
    $stmt_current = $mysqli->prepare("SELECT upline_id, position FROM genealogy_tree WHERE user_id = ?");
    $stmt_current->bind_param("i", $id);
    $stmt_current->execute();
    $current_placement = $stmt_current->get_result()->fetch_assoc();
    $stmt_current->close();

    $placement_is_changing = ($current_placement['upline_id'] != $new_upline_id || $current_placement['position'] != $new_position);

    // --- 2. NEW: Point Recalculation Logic ---
    if ($placement_is_changing) {
        // A. Get all members in the moving downline
        $downline_ids_to_move = getDownlineIds($mysqli, $id);
        
        if (!empty($downline_ids_to_move)) {
            // B. Delete all UNPROCESSED points sourced from this entire downline
            $id_placeholders = implode(',', array_fill(0, count($downline_ids_to_move), '?'));
            $types = str_repeat('i', count($downline_ids_to_move));
            
            $stmt_delete = $mysqli->prepare("DELETE FROM binary_points WHERE status = 'unprocessed' AND source_user_id IN ($id_placeholders)");
            $stmt_delete->bind_param($types, ...$downline_ids_to_move);
            $stmt_delete->execute();
            $stmt_delete->close();
        }
    }

    // --- 3. Update the `users` table (Your existing logic) ---
    if (!empty($password)) {
        if (strlen($password) < 8) { throw new Exception("New password must be at least 8 characters long."); }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt_user = $mysqli->prepare("UPDATE users SET name = ?, username = ?, email = ?, password = ? WHERE id = ? AND role = 'member'");
        $stmt_user->bind_param("ssssi", $name, $username, $email, $hashed_password, $id);
    } else {
        $stmt_user = $mysqli->prepare("UPDATE users SET name = ?, username = ?, email = ? WHERE id = ? AND role = 'member'");
        $stmt_user->bind_param("sssi", $name, $username, $email, $id);
    }
    $stmt_user->execute();
    $stmt_user->close();

    // --- 4. Update the member's position in the `genealogy_tree` table ---
    $stmt_tree = $mysqli->prepare("UPDATE genealogy_tree SET upline_id = ?, position = ? WHERE user_id = ?");
    $stmt_tree->bind_param("isi", $new_upline_id, $new_position, $id);
    $stmt_tree->execute();
    $stmt_tree->close();

    // --- 5. NEW: Re-award points if placement changed ---
    if ($placement_is_changing && !empty($downline_ids_to_move)) {
        // Loop through the moved member and their entire downline
        foreach ($downline_ids_to_move as $source_user_id) {
            // Get this user's package points and their NEW upline path
            $stmt_info = $mysqli->prepare("
                SELECT p.points_value, gt.upline_id, gt.position 
                FROM users u
                JOIN activation_codes ac ON u.id = ac.used_by_id
                JOIN packages p ON ac.package_id = p.id
                JOIN genealogy_tree gt ON u.id = gt.user_id
                WHERE u.id = ? LIMIT 1
            ");
            $stmt_info->bind_param("i", $source_user_id);
            $stmt_info->execute();
            $info_res = $stmt_info->get_result();
            if ($info = $info_res->fetch_assoc()) {
                $points_to_award = $info['points_value'];
                
                // Start walking up the tree from this user's NEW position
                $current_upline_id = $info['upline_id'];
                $current_position = $info['position'];
                $safety_counter = 0;

                while ($current_upline_id > 0 && $safety_counter < 50) {
                    $stmt_points = $mysqli->prepare("INSERT INTO binary_points (user_id, points, position, source_user_id) VALUES (?, ?, ?, ?)");
                    $stmt_points->bind_param("iisi", $current_upline_id, $points_to_award, $current_position, $source_user_id);
                    $stmt_points->execute();
                    $stmt_points->close();

                    // Find the next upline
                    $stmt_next = $mysqli->prepare("SELECT upline_id, position FROM genealogy_tree WHERE user_id = ?");
                    $stmt_next->bind_param("i", $current_upline_id);
                    $stmt_next->execute();
                    if ($next_upline = $stmt_next->get_result()->fetch_assoc()) {
                        $current_upline_id = $next_upline['upline_id'];
                        $current_position = $next_upline['position'];
                    } else { $current_upline_id = 0; }
                    $stmt_next->close();
                    $safety_counter++;
                }
            }
            $stmt_info->close();
        }
    }
    
    $mysqli->commit();
    $_SESSION['message'] = "Member details updated successfully." . ($placement_is_changing ? " Point volume has been recalculated." : "");
    $_SESSION['msg_type'] = "success";

} catch (Exception $e) {
    $mysqli->rollback();
    $_SESSION['message'] = "A critical error occurred: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
    header('Location: member_edit.php?id=' . $id);
    exit();
}

$mysqli->close();
header('Location: member_management.php');
exit();
?>