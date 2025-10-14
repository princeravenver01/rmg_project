<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

// Security check
if ($_SESSION['role'] !== 'admin') {
    echo "<h2>Access Denied</h2></header><div class='card'><p>You do not have permission to view this page.</p></div>";
    require_once '../../src/templates/admin_footer.php';
    exit();
}
?>

<div class="content-header">
    <h2>Staff Management</h2>
    <!-- This button now links to our new form -->
    <a href="staff_add.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add New Staff</a>
</div>

<div class="card">
    <?php
    // Display any messages from the session
    if (isset($_SESSION['message'])) {
        echo '<div class="alert alert-' . $_SESSION['msg_type'] . '" style="padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500; background-color: ' . ($_SESSION['msg_type'] == 'success' ? '#d4edda; color: #155724;' : '#f8d7da; color: #721c24;') . '">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message']);
        unset($_SESSION['msg_type']);
    }
    ?>

    <style> /* Scoped styles for the table */
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid var(--border-color); padding: 15px; text-align: left; }
        th { font-weight: 600; font-size: 14px; color: var(--text-muted); }
        .no-staff { text-align: center; color: var(--text-muted); padding: 20px; }
    </style>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Date Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
    <?php
    $sql = "SELECT id, name, email, created_at FROM users WHERE role = 'staff' ORDER BY id DESC";
    $result = $mysqli->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($staff = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($staff['id']) . "</td>";
            echo "<td>" . htmlspecialchars($staff['name']) . "</td>";
            echo "<td>" . htmlspecialchars($staff['email']) . "</td>";
            echo "<td>" . htmlspecialchars(date('M d, Y', strtotime($staff['created_at']))) . "</td>";
            echo '<td>';
            // Edit Link
            echo '<a href="staff_edit.php?id=' . $staff['id'] . '" style="color: #3498db; text-decoration: none; margin-right: 15px;">Edit</a>';
            
            // Delete Form/Link
            echo '<form action="staff_handle_delete.php" method="POST" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to delete this staff member?\');">';
            echo '<input type="hidden" name="id" value="' . $staff['id'] . '">';
            echo '<button type="submit" style="background: none; border: none; color: #e74c3c; cursor: pointer; padding: 0; font-size: inherit;">Delete</button>';
            echo '</form>';
            
            echo '</td>';
            echo "</tr>";
        }
    } else {
        echo '<tr><td colspan="5" class="no-staff">No staff members found. Click "Add New Staff" to begin.</td></tr>';
    }
    $mysqli->close();
    ?>
</tbody>
    </table>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
?>