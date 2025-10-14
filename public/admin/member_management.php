<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

if ($_SESSION['role'] !== 'admin') {
    exit('Access Denied');
}

// Search and Pagination Logic
$search = isset($_GET['search']) ? $mysqli->real_escape_string($_GET['search']) : '';
$where_clause = '';
if (!empty($search)) {
    // --- FIX: Add username to the search condition ---
    $where_clause = " WHERE role = 'member' AND (name LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%' OR id = '$search')";
} else {
    $where_clause = " WHERE role = 'member'";
}

// --- FIX: Add username to the SELECT statement ---
$sql_members = "SELECT u.id, u.name, u.username, u.email, u.created_at, gt.sponsor_id 
                FROM users u
                LEFT JOIN genealogy_tree gt ON u.id = gt.user_id
                $where_clause
                ORDER BY u.id DESC";

$result = $mysqli->query($sql_members);
?>

<div class="content-header">
    <h2>Member Management</h2>
</div>

<div class="card">
    <div style="margin-bottom: 20px;">
        <form action="member_management.php" method="GET">
            <div class="search-bar" style="max-width: 400px;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Search by ID, Name, Username, or Email..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </form>
    </div>

    <style> /* Table Styles */
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid var(--border-color); padding: 15px; text-align: left; vertical-align: middle; }
        th { font-weight: 600; font-size: 14px; color: var(--text-muted); }
    </style>
    <table>
        <thead>
            <tr>
                <th>Member ID</th>
                <th>Name</th>
                <th>Username</th> <!-- NEW COLUMN -->
                <th>Email</th>
                <th>Sponsor ID</th>
                <th>Date Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while ($member = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $member['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($member['name']) . "</td>";
                    // --- FIX: Display the username ---
                    echo "<td><strong>" . htmlspecialchars($member['username']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($member['email']) . "</td>";
                    echo "<td>" . ($member['sponsor_id'] ? $member['sponsor_id'] : 'N/A') . "</td>";
                    echo "<td>" . date('M d, Y', strtotime($member['created_at'])) . "</td>";
                    echo '<td>';
                    echo '<a href="member_ledger.php?id=' . $member['id'] . '" style="color: #8e44ad; text-decoration: none; margin-right: 15px;">View Ledger</a> | ';
                    echo '<a href="member_edit.php?id=' . $member['id'] . '" style="color: #3498db; text-decoration: none; margin-right: 15px;">Edit</a> | ';
                    echo '<a href="genealogy.php?user_id=' . $member['id'] . '" style="color: #27ae60; text-decoration: none; margin-left: 15px; margin-right: 15px;">View Tree</a> | ';
                    
                    // --- NEW DELETE FORM ---
                    echo '<form action="handle_member_delete.php" method="POST" style="display: inline;" onsubmit="return confirm(\'WARNING: This will permanently delete the member and their unprocessed points. Are you absolutely sure?\');">';
                    echo '<input type="hidden" name="id" value="' . $member['id'] . '">';
                    echo '<button type="submit" style="background: none; border: none; color: #e74c3c; cursor: pointer; padding: 0; font-size: inherit; margin-left: 15px;">Delete</button>';
                    echo '</form>';
                    echo '</td>';
                    echo "</tr>";
                }
            } else {
                echo '<tr><td colspan="7" style="text-align: center; padding: 20px;">No members found.</td></tr>'; // Colspan is now 7
            }
            ?>
        </tbody>
    </table>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
$mysqli->close();
?>