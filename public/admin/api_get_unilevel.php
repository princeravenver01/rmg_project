<?php
session_start();
header('Content-Type: application/json');
require_once '../../src/includes/db_connect.php';

if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin')) {
    echo json_encode(['error' => 'Authentication required.']);
    exit();
}

// Default to the Corp Account ID
$start_user_id = 20; 

// Handle search by username
if (isset($_GET['username']) && !empty(trim($_GET['username']))) {
    $username_search = trim($_GET['username']);
    $stmt_search = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    $stmt_search->bind_param("s", $username_search);
    $stmt_search->execute();
    $result_search = $stmt_search->get_result();
    if ($user = $result_search->fetch_assoc()) {
        $start_user_id = $user['id'];
    } else {
        echo json_encode(['error' => "User with username '$username_search' not found."]);
        exit();
    }
    $stmt_search->close();
} elseif (isset($_GET['user_id'])) {
    $start_user_id = (int)$_GET['user_id'];
}


// --- THE DEFINITIVE FIX: RELIABLE PHP RECURSIVE FUNCTION ---
function getUnilevelDownline($mysqli, $sponsor_id, $current_level, $max_levels) {
    if ($current_level > $max_levels) {
        return [];
    }
    
    // Find all users directly sponsored by the given sponsor_id
    $stmt = $mysqli->prepare("
        SELECT u.id, u.name, u.username, u.created_at
        FROM users u
        JOIN genealogy_tree gt ON u.id = gt.user_id
        WHERE gt.sponsor_id = ? AND u.id != ?
        ORDER BY u.id ASC
    ");
    $stmt->bind_param("ii", $sponsor_id, $sponsor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $children = [];
    while ($row = $result->fetch_assoc()) {
        // Count the directs of THIS child
        $stmt_count = $mysqli->prepare("SELECT COUNT(*) as direct_count FROM genealogy_tree WHERE sponsor_id = ? AND user_id != ?");
        $stmt_count->bind_param("ii", $row['id'], $row['id']);
        $stmt_count->execute();
        $row['direct_count'] = $stmt_count->get_result()->fetch_assoc()['direct_count'];
        $stmt_count->close();

        $row['level'] = $current_level; // Assign the correct, current level
        
        // Recursively call the function for THIS CHILD'S ID to find the next level
        $row['children'] = getUnilevelDownline($mysqli, $row['id'], $current_level + 1, $max_levels);
        
        $children[] = $row;
    }
    $stmt->close();
    return $children;
}
// --- END OF FIX ---


try {
    // 1. Fetch the Root User
    $stmt_root = $mysqli->prepare("SELECT id, name, username FROM users WHERE id = ?");
    $stmt_root->bind_param("i", $start_user_id);
    $stmt_root->execute();
    $root_user = $stmt_root->get_result()->fetch_assoc();
    $stmt_root->close();

    if (!$root_user) {
        throw new Exception("Start user with ID '$start_user_id' not found.");
    }

    // 2. Get the directs count for the root user
    $stmt_root_count = $mysqli->prepare("SELECT COUNT(*) as direct_count FROM genealogy_tree WHERE sponsor_id = ? AND user_id != ?");
    $stmt_root_count->bind_param("ii", $start_user_id, $start_user_id);
    $stmt_root_count->execute();
    $root_user['direct_count'] = $stmt_root_count->get_result()->fetch_assoc()['direct_count'];
    $stmt_root_count->close();
    
    // 3. Assemble the final structure
    $root_user['level'] = 0; // The root is always Level 0
    $root_user['children'] = getUnilevelDownline($mysqli, $start_user_id, 1, 10); // Start searching for Level 1
    
    echo json_encode($root_user);

} catch (Exception $e) {
    echo json_encode(['error' => "An error occurred: " . $e->getMessage()]);
}

$mysqli->close();
?>