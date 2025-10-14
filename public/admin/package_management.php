<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

// Only admins and staff can view packages
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    // Redirect or show access denied
    exit('Access Denied');
}
?>

<div class="content-header">
    <h2>Package Management</h2>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="package_add.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add New Package</a>
    <?php endif; ?>
</div>

<div class="card">
    <?php
    // Display session messages
    if (isset($_SESSION['message'])) {
        echo '<div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500; background-color: ' . ($_SESSION['msg_type'] == 'success' ? '#d4edda; color: #155724;' : '#f8d7da; color: #721c24;') . '">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message'], $_SESSION['msg_type']);
    }
    ?>
    <style> /* Table Styles */
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid var(--border-color); padding: 15px; text-align: left; vertical-align: middle; }
        th { font-weight: 600; font-size: 14px; color: var(--text-muted); }
    </style>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Points</th>
                <th>Status</th>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
    <?php
    $sql = "SELECT * FROM packages ORDER BY id DESC";
    $result = $mysqli->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($package = $result->fetch_assoc()) {
            $status = $package['is_active'] ? '<span style="color: green;">Active</span>' : '<span style="color: red;">Inactive</span>';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($package['name']) . "</td>";
            echo "<td>₱" . number_format($package['price'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($package['points_value']) . "</td>";
            echo "<td>" . $status . "</td>";
            if ($_SESSION['role'] === 'admin') {
                echo '<td>';
                echo '<a href="package_edit.php?id=' . $package['id'] . '" style="color: #3498db; text-decoration: none; margin-right: 15px;">Edit</a>';
                echo '<form action="package_handle_delete.php" method="POST" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to delete this package?\');">';
                echo '<input type="hidden" name="id" value="' . $package['id'] . '">';
                echo '<button type="submit" style="background: none; border: none; color: #e74c3c; cursor: pointer; padding: 0; font-size: inherit;">Delete</button>';
                echo '</form>';
                echo '</td>';
            }
            echo "</tr>";
        }
    } else {
        echo '<tr><td colspan="5" style="text-align: center; padding: 20px;">No packages found.</td></tr>';
    }
    $mysqli->close();
    ?>
</tbody>
    </table>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
?>