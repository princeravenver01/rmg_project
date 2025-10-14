<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') { exit('Access Denied'); }
?>

<div class="content-header">
    <h2>Purchase History</h2>
</div>

<div class="card">
    <style> /* Table Styles */ </style>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Member</th>
                <th>Package</th>
                <th>Price Paid</th>
                <th>Payment</th>
                <th>Processed By</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT ph.*, u.name as member_name, p.name as package_name, admin.name as admin_name
                    FROM purchase_history ph
                    JOIN packages p ON ph.package_id = p.id
                    JOIN users admin ON ph.processed_by_id = admin.id
                    LEFT JOIN users u ON ph.user_id = u.id
                    ORDER BY ph.id DESC";
            $result = $mysqli->query($sql);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                    echo "<td>" . ($row['member_name'] ? htmlspecialchars($row['member_name']) . ' (ID: ' . $row['user_id'] . ')' : 'For New Member') . "</td>";
                    echo "<td>" . htmlspecialchars($row['package_name']) . "</td>";
                    echo "<td>₱" . number_format($row['price_paid'], 2) . "</td>";
                    echo "<td>" . htmlspecialchars($row['payment_method']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['admin_name']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo '<tr><td colspan="6" style="text-align: center;">No purchase records found.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
$mysqli->close();
?>